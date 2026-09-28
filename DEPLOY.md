# Panduan Setup CI/CD & Deploy IT Helpdesk Alita (`ticketing.alita.id`)

Panduan ini untuk setup CI/CD dan deployment otomatis IT Helpdesk Alita di server internal **10.0.5.186** dengan domain **`ticketing.alita.id`**.

> [!IMPORTANT]
> **Isolasi Penuh dari AiBAS-PROD**:
> Server 10.0.5.186 juga menjalankan AiBAS-PROD di Docker (port host 80, 443, dan 5432).
> IT Helpdesk Alita menggunakan **Docker Compose terpisah**:
> - Port Web: **`8088`** (diarahkan dari WAF Sophos)
> - Database PostgreSQL: Berjalan di jaringan internal container (`ticketing_net`), tidak memetakan port host 5432
> - Semua container memiliki prefix nama `ticketing_*`
> **AiBAS-PROD sama sekali tidak tersentuh.**

---

## 1. Arsitektur Deployment

```
Pengguna ──HTTPS:443──► WAF Sophos (ticketing.alita.id)
                             │
                             ▼ HTTP:8088 (Forward Host: ticketing.alita.id)
                    Server 10.0.5.186
                             │
            ┌────────────────┴────────────────┐
            │  Docker Compose (ticketing_net) │
            │                                 │
            │  ticketing_web (Nginx :8088)    │
            │       │                         │
            │       ▼ FastCGI:9000            │
            │  ticketing_app (PHP 8.4-FPM)    │
            │       │                         │
            │       ├─► ticketing_db (PostgreSQL 17)
            │       ├─► ticketing_queue (Email Worker)
            │       └─► ticketing_scheduler (Auto-close cron)
            └─────────────────────────────────┘
```

---

## 2. Alur CI/CD GitHub Actions

```
git push ke branch 'main'
   │
   ▼
[Job 1: Test] (GitHub Cloud Runner: ubuntu-latest)
  ├── Setup PHP 8.4 + ekstensi (pdo_pgsql, zip, intl, mbstring, bcmath)
  ├── Service container PostgreSQL 17
  ├── Composer install
  ├── Laravel Pint (Code style check)
  ├── PHPStan (Static analysis: level 5)
  └── PHPUnit (140+ Feature & Unit tests)
   │
   ▼ (Lolos CI)
[Job 2: Deploy] (Self-Hosted Runner di 10.0.5.186, label: "ticketing")
  ├── Checkout source code terbaru
  ├── Sinkronisasi ke /var/www/ticketing
  ├── docker compose build (menggunakan cache layer)
  ├── docker compose up -d db (tunggu pg_isready)
  ├── docker compose up -d app
  ├── php artisan migrate --force
  ├── php artisan db:seed --class=HelpdeskSeeder --force (idempotent master data)
  ├── php artisan optimize (cache config, routes, views)
  ├── docker compose up -d web queue scheduler
  └── Health check: http://127.0.0.1:8088/up (Host: ticketing.alita.id)
```

---

## 3. Langkah Setup Server (Hanya Perlu Dilakukan Sekali)

### A. Siapkan Folder & File `.env` Production

Jalankan perintah ini di server (sebagai user `kai` atau `deploy`):

```bash
# 1. Jalankan script setup
bash /var/www/it-helpdesk-alita/deploy/setup-docker.sh

# 2. Buka dan lengkapi .env production
nano /var/www/ticketing/.env
```

Isi variabel penting di `/var/www/ticketing/.env`:

| Variabel | Keterangan |
| --- | --- |
| `DB_PASSWORD` | Buat password database yang kuat (contoh: acak 24 karakter) |
| `MAIL_PASSWORD` | Password email SMTP akun `aibas.notification@alita.id` (wajib dalam tanda kutip `"..."`) |
| `HELPDESK_ADMIN_EMAIL` | Email admin helpdesk penerima notifikasi tiket baru (contoh: `rio@alita.id`) |
| `TRUSTED_PROXIES` | Biarkan `*` agar header dari Sophos WAF diteruskan dengan benar |

---

### B. Pasang GitHub Actions Self-Hosted Runner

1. Buka repo GitHub Anda: **`https://github.com/Rioagusto1808/it-helpdesk-alita`**
2. Buka tab **Settings** → **Actions** → **Runners** → klik **New self-hosted runner**
3. Pilih OS **Linux**, Architecture **x64**
4. Masuk sebagai user `deploy` di terminal server:

```bash
sudo -iu deploy
mkdir -p ~/actions-runner && cd ~/actions-runner

# Download runner (sesuai URL dan hash dari halaman GitHub)
curl -o actions-runner-linux-x64-2.322.0.tar.gz -L https://github.com/actions/runner/releases/download/v2.322.0/actions-runner-linux-x64-2.322.0.tar.gz
tar xzf ./actions-runner-linux-x64-2.322.0.tar.gz

# Configure runner (GANTI <TOKEN_DARI_GITHUB> dengan token yang muncul di layar GitHub)
./config.sh --url https://github.com/Rioagusto1808/it-helpdesk-alita \
            --token <TOKEN_DARI_GITHUB> \
            --name ticketing-prod \
            --labels ticketing \
            --unattended
```

5. Pasang sebagai systemd service agar runner otomatis menyala jika server restart:

```bash
sudo ./svc.sh install deploy
sudo ./svc.sh start
```

Periksa status runner:
```bash
sudo ./svc.sh status
```
Di halaman GitHub (Settings → Actions → Runners), status runner `ticketing-prod` akan berubah menjadi **Idle** (warna hijau).

---

### C. Konfigurasi WAF Sophos

Konfigurasikan di Sophos WAF (Firewall UTM / XG):
1. **Real Webserver**:
   - Host: `10.0.5.186`
   - Port: `8088`
   - Type: `HTTP`
2. **Virtual Webserver**:
   - Domain: `ticketing.alita.id`
   - Port: `443` (HTTPS dengan SSL certificate)
   - Real Webserver: Pilih Real Webserver di atas (`10.0.5.186:8088`)
   - Centang opsi: **Pass Host Header** (`Host: ticketing.alita.id`) dan **X-Forwarded-For** / **X-Forwarded-Proto**.

---

### D. Buat Akun Admin Pertama

Setelah deploy pertama berhasil berjalan:
```bash
docker exec -it ticketing_app php artisan helpdesk:make-admin
```
Masukkan nama, email, dan password untuk login admin di `https://ticketing.alita.id/admin/login`.

---

## 4. Operasional Sehari-hari

| Kebutuhan | Perintah / Cara |
| --- | --- |
| **Deploy Rilis Baru** | Cukup `git push` ke branch `main`. CI akan otomatis mengetes, lalu CD akan men-deploy ke server. |
| **Deploy Manual** | Jalankan `bash /var/www/ticketing/deploy/deploy-docker.sh` |
| **Cek Status Container** | `docker compose -f /var/www/ticketing/docker-compose.yml ps` |
| **Lihat Log Aplikasi** | `docker compose -f /var/www/ticketing/docker-compose.yml logs -f app web` |
| **Lihat Log Email / Worker** | `docker compose -f /var/www/ticketing/docker-compose.yml logs -f queue` |
| **Restart Service** | `docker compose -f /var/www/ticketing/docker-compose.yml restart` |
| **Backup Database** | `docker exec -t ticketing_postgres pg_dump -U alita alita_helpdesk > backup_$(date +%F).sql` |
| **Restore Database** | `cat backup.sql \| docker exec -i ticketing_postgres psql -U alita alita_helpdesk` |
