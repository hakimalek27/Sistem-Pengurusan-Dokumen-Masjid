#!/usr/bin/env bash
# Prasyarat sekali: rclone config — remote "cosb" (s3 endpoint ap-jakarta) dan
# remote "gdrive-crypt" (jenis crypt membalut Google Drive; kata laluan crypt disimpan LUAR server)
set -euo pipefail
rclone sync cosb:${COS_BACKUP_BUCKET}/ gdrive-crypt:diwan-offsite/ --transfers 4 --log-file /var/log/diwan-offsite.log
