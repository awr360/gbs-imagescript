#!/bin/bash
set -e

# Validate required env vars
if [ -z "$GDRIVE_FOLDER_ID" ] || [ -z "$GDRIVE_API_KEY" ]; then
  echo "ERROR: GDRIVE_FOLDER_ID and GDRIVE_API_KEY must be set."
  exit 1
fi

SYNC_CMD="/usr/local/bin/sync.py"

# Initial sync on startup
echo "[entrypoint] Running initial sync..."
$SYNC_CMD

# Set up cron to sync every 5 minutes
echo "*/5 * * * * root GDRIVE_API_KEY=\"${GDRIVE_API_KEY}\" GDRIVE_FOLDER_ID=\"${GDRIVE_FOLDER_ID}\" ${SYNC_CMD} > /proc/1/fd/1 2>&1" > /etc/cron.d/gdrive-sync
chmod 0644 /etc/cron.d/gdrive-sync

# Start cron in background
cron

echo "[entrypoint] Cron started. Sync runs every 5 minutes."

# Start Apache in foreground
exec apache2-foreground
