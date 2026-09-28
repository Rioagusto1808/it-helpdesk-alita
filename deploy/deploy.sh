#!/usr/bin/env bash
# Deploy atomik IT Helpdesk Alita.
#
# Membangun rilis baru di $APP_DIR/releases/<waktu>-<commit>, menjalankan migrasi dan cache,
# lalu memindahkan symlink $APP_DIR/current. Jika health check gagal, symlink dikembalikan ke rilis sebelumnya.
# Dijalankan otomatis oleh self-hosted runner GitHub Actions di server (lihat DEPLOY.md),
# atau manual dari folder hasil clone:  APP_DIR=/var/www/ticketing bash deploy/deploy.sh
set -euo pipefail
umask 0002 # file baru bisa ditulis grup www-data (php-fpm dan queue worker)

APP_DIR="${APP_DIR:-/var/www/ticketing}"
PHP="${PHP_BIN:-php}" # contoh: /usr/bin/php8.4 jika server punya beberapa versi PHP
COMPOSER="${COMPOSER_BIN:-$(command -v composer)}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1/up}"
HEALTH_HOST="${HEALTH_HOST:-ticketing.alita.id}"
# Opsional, misalnya "sudo systemctl reload php8.4-fpm" jika opcache.validate_timestamps=0.
RELOAD_CMD="${RELOAD_CMD:-}"

SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REV="$(git -C "$SRC" rev-parse --short HEAD 2>/dev/null || echo manual)"
SHARED="$APP_DIR/shared"
RELEASE="$APP_DIR/releases/$(date +%Y%m%d%H%M%S)-$REV"
CURRENT="$APP_DIR/current"

log() { printf '\n==> %s\n' "$*"; }

switch_to() {
    ln -sfn "$1" "$APP_DIR/current.tmp"
    mv -Tf "$APP_DIR/current.tmp" "$CURRENT"
}

if [ ! -f "$SHARED/.env" ]; then
    echo "Belum ada $SHARED/.env. Jalankan setup awal dulu (DEPLOY.md, langkah 3)." >&2
    exit 1
fi

log "Rilis $REV → $RELEASE"
mkdir -p "$RELEASE"
rsync -a --delete \
    --exclude=.git --exclude=.github --exclude=tests --exclude=docs --exclude=deploy \
    --exclude=vendor --exclude=node_modules --exclude=storage --exclude=.env \
    --exclude='.phpunit*' --exclude=phpunit.xml --exclude=phpstan.neon --exclude=pint.json \
    "$SRC/" "$RELEASE/"

# .env dan storage (lampiran, log) dipakai bersama semua rilis.
ln -sfn "$SHARED/.env" "$RELEASE/.env"
ln -sfn "$SHARED/storage" "$RELEASE/storage"
mkdir -p "$RELEASE/bootstrap/cache"

cd "$RELEASE"

log "Composer install (tanpa paket dev)"
"$PHP" "$COMPOSER" install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

log "Migrasi database + data master"
"$PHP" artisan migrate --force --no-interaction
# Idempotent: kategori dan layanan/modul awal. Tidak pernah membuat tiket contoh.
"$PHP" artisan db:seed --class='Database\Seeders\HelpdeskSeeder' --force --no-interaction

log "Cache config, route, view"
# Versi aset = commit ini, jadi browser langsung memuat CSS/JS terbaru (env nyata menang atas .env).
APP_ASSET_VERSION="$REV" "$PHP" artisan optimize

PREVIOUS="$(readlink -f "$CURRENT" 2>/dev/null || true)"

log "Aktifkan rilis"
switch_to "$RELEASE"
if [ -n "$RELOAD_CMD" ]; then eval "$RELOAD_CMD"; fi
# Worker menyelesaikan job yang sedang jalan lalu keluar; systemd menyalakannya lagi di rilis baru.
"$PHP" artisan queue:restart

log "Health check $HEALTH_URL (Host: $HEALTH_HOST)"
healthy=false
for _ in 1 2 3 4 5; do
    if curl -fsS --max-time 10 -H "Host: $HEALTH_HOST" "$HEALTH_URL" >/dev/null; then
        healthy=true
        break
    fi
    sleep 3
done

if [ "$healthy" != true ]; then
    echo "Health check gagal." >&2
    if [ -n "$PREVIOUS" ] && [ -d "$PREVIOUS" ] && [ "$PREVIOUS" != "$RELEASE" ]; then
        # Migrasi yang sudah jalan tidak di-rollback; migrasi harus tetap kompatibel dengan rilis sebelumnya.
        switch_to "$PREVIOUS"
        if [ -n "$RELOAD_CMD" ]; then eval "$RELOAD_CMD"; fi
        "$PHP" "$PREVIOUS/artisan" queue:restart
        echo "Dikembalikan ke $PREVIOUS" >&2
    fi
    exit 1
fi

log "Hapus rilis lama (simpan $KEEP_RELEASES terakhir)"
cd "$APP_DIR/releases"
ls -1dt -- */ | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf --

log "Selesai: $REV aktif di $CURRENT"
