#!/bin/bash
set -e

# Validate required env vars
if [ -z "$GDRIVE_FOLDER_ID" ] || [ -z "$GDRIVE_API_KEY" ]; then
  echo "ERROR: GDRIVE_FOLDER_ID and GDRIVE_API_KEY must be set."
  exit 1
fi

SYNC_CMD="/usr/local/bin/sync.py"

# Preflight: verify API key and folder are accessible before syncing
echo "[entrypoint] Checking Google Drive API access..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
  "https://www.googleapis.com/drive/v3/files?q='${GDRIVE_FOLDER_ID}'+in+parents+and+trashed=false&key=${GDRIVE_API_KEY}&fields=files(id)&pageSize=1")

if [ "$HTTP_STATUS" = "200" ]; then
  echo "[entrypoint] API key OK. Folder ${GDRIVE_FOLDER_ID} is accessible."
else
  echo "[entrypoint] ERROR: Drive API returned HTTP ${HTTP_STATUS}. Check your API key and folder ID."
  exit 1
fi

# Set up cron to sync every 5 minutes
echo "*/5 * * * * root GDRIVE_API_KEY=\"${GDRIVE_API_KEY}\" GDRIVE_FOLDER_ID=\"${GDRIVE_FOLDER_ID}\" ${SYNC_CMD} > /proc/1/fd/1 2>&1" > /etc/cron.d/gdrive-sync
chmod 0644 /etc/cron.d/gdrive-sync

# Start cron in background
cron

# Run initial sync in background so Apache starts immediately
echo "[entrypoint] Running initial sync in background..."
$SYNC_CMD > /proc/1/fd/1 2>&1 &

echo "[entrypoint] Cron started. Sync runs every 5 minutes."

# Start Apache in foreground
exec apache2-foreground
