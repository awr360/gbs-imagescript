#!/bin/bash
set -e

# Validate required env vars
if [ -z "$GDRIVE_FOLDER_ID" ] || [ -z "$GDRIVE_CLIENT_ID" ] || [ -z "$GDRIVE_CLIENT_SECRET" ] || [ -z "$GDRIVE_REFRESH_TOKEN" ]; then
  echo "ERROR: GDRIVE_FOLDER_ID, GDRIVE_CLIENT_ID, GDRIVE_CLIENT_SECRET and GDRIVE_REFRESH_TOKEN must be set."
  exit 1
fi

SYNC_CMD="/usr/local/bin/sync.py"

# Preflight: verify API key and folder are accessible before syncing
echo "[entrypoint] Checking Google Drive API access..."
ACCESS_TOKEN=$(curl -s -X POST https://oauth2.googleapis.com/token \
  -d "client_id=${GDRIVE_CLIENT_ID}&client_secret=${GDRIVE_CLIENT_SECRET}&refresh_token=${GDRIVE_REFRESH_TOKEN}&grant_type=refresh_token" \
  | python3 -c "import sys,json; print(json.load(sys.stdin).get('access_token',''))")

if [ -z "$ACCESS_TOKEN" ]; then
  echo "[entrypoint] ERROR: Failed to obtain access token. Check your OAuth credentials."
  exit 1
fi

HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}" \
  "https://www.googleapis.com/drive/v3/files?q='${GDRIVE_FOLDER_ID}'+in+parents+and+trashed=false&fields=files(id)&pageSize=1")

if [ "$HTTP_STATUS" = "200" ]; then
  echo "[entrypoint] OAuth OK. Folder ${GDRIVE_FOLDER_ID} is accessible."
else
  echo "[entrypoint] ERROR: Drive API returned HTTP ${HTTP_STATUS}. Check folder ID and permissions."
  exit 1
fi

# Set up cron to sync every 5 minutes
echo "*/5 * * * * root GDRIVE_FOLDER_ID=\"${GDRIVE_FOLDER_ID}\" GDRIVE_CLIENT_ID=\"${GDRIVE_CLIENT_ID}\" GDRIVE_CLIENT_SECRET=\"${GDRIVE_CLIENT_SECRET}\" GDRIVE_REFRESH_TOKEN=\"${GDRIVE_REFRESH_TOKEN}\" ${SYNC_CMD} > /proc/1/fd/1 2>&1" > /etc/cron.d/gdrive-sync
chmod 0644 /etc/cron.d/gdrive-sync

# Start cron in background
cron

# Run initial sync in background so Apache starts immediately
echo "[entrypoint] Running initial sync in background..."
$SYNC_CMD > /proc/1/fd/1 2>&1 &

echo "[entrypoint] Cron started. Sync runs every 5 minutes."

# Start Apache in foreground
exec apache2-foreground
