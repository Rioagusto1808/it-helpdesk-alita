#!/usr/bin/env bash
# Setup sekali di server: struktur folder, storage bersama, dan .env production dengan APP_KEY baru.
# Aman dijalankan ulang (tidak menimpa .env yang sudah ada). Tidak memasang paket dan tidak menyentuh aplikasi lain.
# Jalankan sebagai user deploy (anggota grup www-data):  APP_DIR=/var/www/ticketing bash deploy/setup-server.sh
set -euo pipefail
umask 0002

APP_DIR="${APP_DIR:-/var/www/ticketing}"
WEB_GROUP="${WEB_GROUP:-www-data}"
PHP="${PHP_BIN:-php}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SHARED="$APP_DIR/shared"

mkdir -p "$APP_DIR/releases" \
    "$SHARED/storage/app/private" \
    "$SHARED/storage/framework/cache/data" \
    "$SHARED/storage/framework/sessions" \
    "$SHARED/storage/framework/views" \
    "$SHARED/storage/logs"

# Grup www-data bisa menulis storage; setgid agar file baru ikut grup yang sama.
chgrp -R "$WEB_GROUP" "$SHARED/storage"
find "$SHARED/storage" -type d -exec chmod 2775 {} +

if [ -f "$SHARED/.env" ]; then
    echo "$SHARED/.env sudah ada, tidak diubah."
else
    key="base64:$("$PHP" -r 'echo base64_encode(random_bytes(32));')"
    sed "s|^APP_KEY=.*|APP_KEY=$key|" "$SRC/env.production.example" > "$SHARED/.env"
    chgrp "$WEB_GROUP" "$SHARED/.env"
    chmod 0640 "$SHARED/.env"
    echo "Dibuat $SHARED/.env dengan APP_KEY baru."
fi

cat <<EOF

Selesai. Langkah berikutnya (DEPLOY.md):
  1. Isi password database dan email di $SHARED/.env
  2. Pasang vhost nginx, service queue, dan cron dari folder deploy/
  3. Pasang self-hosted runner GitHub dengan label "ticketing"
  4. Push ke main (atau Run workflow) untuk deploy pertama
EOF
