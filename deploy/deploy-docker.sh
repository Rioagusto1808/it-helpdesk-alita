#!/usr/bin/env bash
# Deploy IT Helpdesk Alita via Docker Compose
# Terisolasi penuh dari AiBAS (port 8088 untuk web, db di jaringan internal docker)
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ticketing}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:8088/up}"
HEALTH_HOST="${HEALTH_HOST:-ticketing.alita.id}"

SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

log() { printf '\n==> %s\n' "$*"; }

if [ ! -f "$APP_DIR/.env" ]; then
    echo "ERROR: $APP_DIR/.env belum ada! Siapkan file .env dari deploy/env.docker.example terlebih dahulu." >&2
    exit 1
fi

log "Sinkronisasi kode ke $APP_DIR"
if [ "$SRC" != "$APP_DIR" ]; then
    mkdir -p "$APP_DIR"
    rsync -a --delete \
        --exclude=.git --exclude=.github --exclude=tests --exclude=docs \
        --exclude=vendor --exclude=node_modules --exclude=storage --exclude=.env \
        "$SRC/" "$APP_DIR/"
fi

cd "$APP_DIR"

log "Membangun container Docker..."
docker compose build

log "Memulai database PostgreSQL (ticketing_postgres)..."
docker compose up -d db

log "Menunggu database siap..."
for i in $(seq 1 30); do
    if docker compose exec -T db pg_isready -U "${DB_USERNAME:-alita}" -d "${DB_DATABASE:-alita_helpdesk}" >/dev/null 2>&1; then
        echo "Database siap!"
        break
    fi
    sleep 2
    if [ "$i" -eq 30 ]; then
        echo "ERROR: Database tidak siap setelah 60 detik." >&2
        docker compose logs db
        exit 1
    fi
done

log "Memulai service app (PHP-FPM)..."
docker compose up -d app

log "Menjalankan migrasi database..."
docker compose exec -T app php artisan migrate --force --no-interaction

log "Menjalankan seeder master (idempotent)..."
docker compose exec -T app php artisan db:seed --class='Database\Seeders\HelpdeskSeeder' --force --no-interaction

log "Cache config, route, dan view..."
docker compose exec -T app php artisan optimize

log "Memulai web (Nginx port 8088), queue worker, dan scheduler..."
docker compose up -d web queue scheduler
# Restart web agar Nginx selalu me-resolve IP baru dari container app
docker compose restart web

log "Memeriksa health check di $HEALTH_URL (Host: $HEALTH_HOST)..."
healthy=false
for attempt in $(seq 1 10); do
    echo "Pengecekan health check (percobaan $attempt/10)..."
    if curl -fsS --max-time 5 -H "Host: $HEALTH_HOST" "$HEALTH_URL" >/dev/null 2>&1; then
        healthy=true
        break
    fi
    sleep 2
done

if [ "$healthy" != true ]; then
    echo "ERROR: Health check gagal di $HEALTH_URL" >&2
    docker compose ps
    docker compose logs --tail=50 web app
    exit 1
fi

log "Membersihkan image lama..."
docker image prune -f || true

log "Deploy sukses! IT Helpdesk Alita aktif di port 8088."
