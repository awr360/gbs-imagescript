#!/usr/bin/env python3
import os
import requests
from pathlib import Path
import sys
import json
from concurrent.futures import ThreadPoolExecutor, as_completed

FOLDER_ID = os.environ.get('GDRIVE_FOLDER_ID')
CLIENT_ID = os.environ.get('GDRIVE_CLIENT_ID')
CLIENT_SECRET = os.environ.get('GDRIVE_CLIENT_SECRET')
REFRESH_TOKEN = os.environ.get('GDRIVE_REFRESH_TOKEN')
DEST_DIR = '/var/www/html/images'
BASE_URL = 'https://www.googleapis.com/drive/v3/files'
DOWNLOAD_WORKERS = 4

if not all([FOLDER_ID, CLIENT_ID, CLIENT_SECRET, REFRESH_TOKEN]):
    print("ERROR: GDRIVE_FOLDER_ID, GDRIVE_CLIENT_ID, GDRIVE_CLIENT_SECRET and GDRIVE_REFRESH_TOKEN must be set.")
    sys.exit(1)

def get_access_token():
    r = requests.post('https://oauth2.googleapis.com/token', data={
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET,
        'refresh_token': REFRESH_TOKEN,
        'grant_type': 'refresh_token',
    })
    r.raise_for_status()
    return r.json()['access_token']

def list_files(parent_id, token):
    params = {
        'q': f"'{parent_id}' in parents and trashed = false",
        'fields': 'nextPageToken,files(id, name, mimeType)',
        'supportsAllDrives': 'true',
        'includeItemsFromAllDrives': 'true',
        'pageSize': 1000
    }
    headers = {'Authorization': f'Bearer {token}'}
    files = []
    try:
        while True:
            r = requests.get(BASE_URL, params=params, headers=headers)
            r.raise_for_status()
            data = r.json()
            files.extend(data.get('files', []))
            next_token = data.get('nextPageToken')
            if not next_token:
                break
            params['pageToken'] = next_token
    except Exception as e:
        print(f"Error listing files in {parent_id}: {e}")
    return files

def download_file(file_id, dest_path, token):
    headers = {'Authorization': f'Bearer {token}'}
    try:
        with requests.get(f"{BASE_URL}/{file_id}", params={'alt': 'media'}, headers=headers, stream=True) as r:
            r.raise_for_status()
            with open(dest_path, 'wb') as f:
                for chunk in r.iter_content(chunk_size=65536):
                    f.write(chunk)
        print(f"  Downloaded: {dest_path}")
        return True
    except Exception as e:
        print(f"  Failed to download {dest_path}: {e}")
        return False

def collect_files(folder_id, local_path, token, relative_path=""):
    Path(local_path).mkdir(parents=True, exist_ok=True)
    items = list_files(folder_id, token)
    active_paths = []
    pending_downloads = []

    for item in items:
        name = item['name']
        item_id = item['id']
        mime_type = item['mimeType']
        target_path = os.path.join(local_path, name)
        rel_path = os.path.join(relative_path, name) if relative_path else name

        if mime_type == 'application/vnd.google-apps.folder':
            sub_active, sub_pending = collect_files(item_id, target_path, token, rel_path)
            active_paths.extend(sub_active)
            pending_downloads.extend(sub_pending)
        else:
            active_paths.append(rel_path)
            if not os.path.exists(target_path):
                pending_downloads.append((item_id, target_path))

    return active_paths, pending_downloads

if __name__ == '__main__':
    print(f"Starting sync: Drive:{FOLDER_ID} -> {DEST_DIR}")

    token = get_access_token()

    # 1. Walk Drive tree
    active_files, pending = collect_files(FOLDER_ID, DEST_DIR, token)
    print(f"Found {len(active_files)} files, {len(pending)} need downloading.")

    # 2. Download missing files in parallel
    if pending:
        with ThreadPoolExecutor(max_workers=DOWNLOAD_WORKERS) as pool:
            futures = {pool.submit(download_file, fid, path, token): path for fid, path in pending}
            for future in as_completed(futures):
                future.result()

    # 3. Write the manifest
    manifest_path = os.path.join(DEST_DIR, 'manifest.json')
    with open(manifest_path, 'w') as f:
        json.dump(active_files, f)
    print(f"Manifest created with {len(active_files)} files.")

    # 4. Prune local files no longer in Google Drive
    valid_paths = set(active_files)
    valid_paths.add('manifest.json')

    for root, dirs, files in os.walk(DEST_DIR, topdown=False):
        for name in files:
            full_path = os.path.join(root, name)
            rel_path = os.path.relpath(full_path, DEST_DIR)
            if rel_path not in valid_paths:
                os.remove(full_path)
                print(f"  Deleted orphaned file: {rel_path}")

        for name in dirs:
            full_path = os.path.join(root, name)
            if not os.listdir(full_path):
                os.rmdir(full_path)
                print(f"  Deleted empty directory: {name}")

    print("Sync finished.")
