#!/usr/bin/env bash
set -euo pipefail

ENV_FILE="/root/tokombaemi/.env"
BACKUP_DIR="/root/tokombaemi/storage/backups"
KEEP_DAYS=14
APP_DIR="/root/tokombaemi"

[ -f "$ENV_FILE" ] || { echo "ERROR: $ENV_FILE not found" >&2; exit 1; }

DB_HOST=$(grep -E '^DB_HOST=' "$ENV_FILE" | cut -d= -f2- | tr -d '"')
DB_PORT=$(grep -E '^DB_PORT=' "$ENV_FILE" | cut -d= -f2- | tr -d '"')
DB_DATABASE=$(grep -E '^DB_DATABASE=' "$ENV_FILE" | cut -d= -f2- | tr -d '"')
DB_USERNAME=$(grep -E '^DB_USERNAME=' "$ENV_FILE" | cut -d= -f2- | tr -d '"')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' "$ENV_FILE" | cut -d= -f2- | tr -d '"')

[ -d "$BACKUP_DIR" ] || mkdir -p "$BACKUP_DIR"

STAMP=$(date +%Y%m%d-%H%M%S)
TMP_DIR="$BACKUP_DIR/tmp-$STAMP"
OUT_FILE="$BACKUP_DIR/tokombaemi-$STAMP.tar.gz"

mkdir -p "$TMP_DIR"

# 1. Database dump (password via env, tidak tampil di process list)
MYSQL_PWD="$DB_PASSWORD" mysqldump \
  --skip-ssl \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USERNAME" \
  --single-transaction --routines --triggers \
  "$DB_DATABASE" | gzip > "$TMP_DIR/database.sql.gz"

# 2. File upload (foto produk)
if [ -d "$APP_DIR/storage/app/public" ]; then
  tar -czf "$TMP_DIR/public-files.tar.gz" -C "$APP_DIR/storage/app" public
fi

# 3. Konfigurasi (.env) — wajib untuk restorasi penuh
cp "$ENV_FILE" "$TMP_DIR/.env"

tar -czf "$OUT_FILE" -C "$TMP_DIR" .
rm -rf "$TMP_DIR"

find "$BACKUP_DIR" -name 'stock-*.tar.gz' -mtime +"$KEEP_DAYS" -delete

echo "Backup OK: $OUT_FILE ($(du -h "$OUT_FILE" | cut -f1))"
