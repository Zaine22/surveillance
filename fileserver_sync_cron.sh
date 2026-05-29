#!/bin/bash
# FileServer Cron Job - Sync unscanned files to CleanFileServer
# This script syncs files from FileServer to CleanFileServer automatically
# Add to crontab on FileServer (34.81.79.232):
# */5 * * * * /home/user3/fileserver_sync_cron.sh >> /home/user3/sync_cron.log 2>&1

# Configuration
SOURCE_DIR="/home/rsyncbot/unscann-files/"
DEST_USER="user3"
DEST_HOST="192.168.0.10"
DEST_DIR="/home/rsyncbot/clean-files/"
LOG_FILE="/home/user3/sync_cron.log"

# Timestamp for logging
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting sync from FileServer to CleanFileServer"

# Rsync command
# -a: archive mode (preserves permissions, timestamps, etc.)
# -v: verbose
# -z: compress during transfer
# --ignore-existing: skip files that already exist on destination (prevents duplicates)
# --remove-source-files: remove source files after successful transfer (optional)
rsync -avz \
  --ignore-existing \
  --timeout=300 \
  "$SOURCE_DIR" \
  "${DEST_USER}@${DEST_HOST}:${DEST_DIR}"

# Check exit status
if [ $? -eq 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Sync completed successfully"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Sync failed with exit code $?"
fi

echo "---"
