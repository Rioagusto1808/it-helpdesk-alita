#!/usr/bin/env bash
# Setup awal Docker untuk IT Helpdesk Alita di /var/www/ticketing
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ticketing}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

mkdir -p "$APP_DIR"

if [ -f "$APP_DIR/.env" ]; then
    echo "$APP_DIR/.env sudah ada, tidak diubah."
else
    key="base64:$(php8.4 -r 'echo base64_encode(random_bytes(32));' 2>/dev/null || openssl rand -base64 32)"
    sed "s|^APP_KEY=.*|APP_KEY=$key|" "$SRC/deploy/env.docker.example" > "$APP_DIR/.env"
    chmod 0640 "$APP_DIR/.env"
    echo "Dibuat $APP_DIR/.env dengan APP_KEY baru."
fi

cat <<EOF

Setup awal selesai!
Langkah berikutnya:
  1. Sunting password di $APP_DIR/.env:
       nano $APP_DIR/.env
     Isi DB_PASSWORD (misal password acak aman) dan MAIL_PASSWORD.
  2. Pasang GitHub Actions runner dengan label 'ticketing' di server ini.
  3. Konfigurasi WAF Sophos:
     Arahkan virtual webserver 'ticketing.alita.id' ke Real Webserver '10.0.5.186:8088' (HTTP).
  4. Jalankan deploy manual atau via GitHub Actions:
     bash deploy/deploy-docker.sh
EOF
