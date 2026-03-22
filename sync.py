#!/usr/bin/env python3
import os
import requests
from pathlib import Path
import sys
import json

API_KEY = os.environ.get('GDRIVE_API_KEY')
FOLDER_ID = os.environ.get('GDRIVE_FOLDER_ID')
DEST_DIR = '/var/www/html/images'
BASE_URL = 'https://www.googleapis.com/drive/v3/files'

if not API_KEY or not FOLDER_ID:
    print("ERROR: GDRIVE_API_KEY and GDRIVE_FOLDER_ID must be set.")
    sys.exit(1)

def list_files(parent_id):
    params = {
        'q': f"'{parent_id}' in parents and trashed = false",
        'key': API_KEY,
        'fields': 'files(id, name, mimeType)',
        'supportsAllDrives': 'true',
        'includeItemsFromAllDrives': 'true',
        'pageSize': 1000
    }
    try:
        r = requests.get(BASE_URL, params=params)
        r.raise_for_status()
        return r.json().get('files', [])
    except Exception as e:
        print(f"Error listing files in {parent_id}: {e}")
        return []

def download_file(file_id, dest_path):
    params = {'alt': 'media', 'key': API_KEY}
    try:
        with requests.get(f"{BASE_URL}/{file_id}", params=params, stream=True) as r:
            r.raise_for_status()
            with open(dest_path, 'wb') as f:
                for chunk in r.iter_content(chunk_size=8192):
                    f.write(chunk)
        print(f"  Downloaded: {dest_path}")
    except Exception as e:
        print(f"  Failed to download {dest_path}: {e}")

def sync_folder(folder_id, local_path, relative_path=""):
    Path(local_path).mkdir(parents=True, exist_ok=True)
    items = list_files(folder_id)
    active_paths = []
    
    for item in items:
        name = item['name']
        item_id = item['id']
        mime_type = item['mimeType']
        target_path = os.path.join(local_path, name)
        rel_path = os.path.join(relative_path, name) if relative_path else name
        
        if mime_type == 'application/vnd.google-apps.folder':
            active_paths.extend(sync_folder(item_id, target_path, rel_path))
        else:
            # We only track non-folder files in the manifest
            if name.lower().endswith(('.jpg', '.jpeg', '.png', '.gif', '.webp')):
                active_paths.append(rel_path)
                if not os.path.exists(target_path):
                    download_file(item_id, target_path)
            
    return active_paths

if __name__ == '__main__':
    print(f"Starting sync: Drive:{FOLDER_ID} -> {DEST_DIR}")
    
    # 1. Sync and get all active relative paths from Google Drive
    active_files = sync_folder(FOLDER_ID, DEST_DIR)
    
    # 2. Write the manifest
    manifest_path = os.path.join(DEST_DIR, 'manifest.json')
    with open(manifest_path, 'w') as f:
        json.dump(active_files, f)
    print(f"Manifest created with {len(active_files)} files.")

    # 3. Prune local files that are no longer in Google Drive
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
