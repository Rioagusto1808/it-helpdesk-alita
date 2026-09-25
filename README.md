# IT Helpdesk Alita

Aplikasi web untuk melapor kendala IT (ITApps → modul, ITInfra → layanan), memantau antrian secara transparan, dan mengelola tiket oleh tim IT.

- **Pemohon** tidak perlu login: kirim tiket di `/`, lihat antrian di `/antrian`, pantau dan balas tiket lewat link di email (atau minta ulang di `/lacak`).
- **Tim IT** login di `/admin`: dashboard, daftar dan detail tiket, master data layanan/modul, user, audit log, dan email log.
- Setiap tiket baru dikirim ke `HELPDESK_ADMIN_EMAIL` (default `rio@alita.id`). Semua email lewat queue dan tercatat di email log.

Spesifikasi lengkap: [docs/PRD.md](docs/PRD.md). Aturan kode: [docs/BACKEND_RULES.md](docs/BACKEND_RULES.md) dan [docs/FRONTEND_RULES.md](docs/FRONTEND_RULES.md).

## Stack

| Komponen | Pilihan |
| --- | --- |
| Bahasa / framework | PHP 8.2+ (diuji di 8.5), Laravel 12 |
| Database | PostgreSQL 16+ (minimal 15) |
| Tampilan | Blade + CSS + JavaScript vanilla di `public/`, **tanpa npm/Vite/build step** |
| Queue | driver `database`, worker dijalankan Supervisor |
| Scheduler | `php artisan schedule:run` via cron tiap menit |
| Kualitas | PHPUnit (PostgreSQL), Laravel Pint, Larastan level 6 |

## Instalasi lokal

Langkah lengkap untuk Windows + Laragon ada di [SETUP.md](SETUP.md). Ringkasnya:

```bash
composer install
cp .env.example .env          # isi DB_PASSWORD
php artisan key:generate
php artisan migrate --seed    # master data + 30 tiket contoh (hanya di APP_ENV=local)
php artisan helpdesk:make-admin
```

Jalankan dalam tiga terminal:

```bash
php artisan serve             # http://localhost:8000
php artisan queue:work --tries=3
php artisan schedule:work     # auto-close tiket (pengganti cron di lokal)
```

Selama `MAIL_MAILER=log`, email tidak benar-benar dikirim, tapi ditulis ke `storage/logs/laravel.log`.

## Konfigurasi `.env`

| Variabel | Contoh | Keterangan |
| --- | --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | **Wajib** `false` di production |
| `APP_URL` | `https://helpdesk.alita.id` | Dipakai di link email dan signed URL tracking |
| `APP_TIMEZONE` / `APP_LOCALE` | `Asia/Jakarta` / `id` | |
| `APP_ASSET_VERSION` | `1` | Naikkan setiap kali CSS/JS berubah agar cache browser diperbarui |
| `DB_*` | `pgsql`, `alita_helpdesk`, `alita` | Database PostgreSQL |
| `SESSION_SECURE_COOKIE` | `true` | **Wajib** `true` di production (HTTPS) |
| `QUEUE_CONNECTION` | `database` | |
| `MAIL_*` | lihat di bawah | SMTP kantor |
| `HELPDESK_ADMIN_EMAIL` | `rio@alita.id,it@alita.id` | Penerima email tiket baru, pisahkan dengan koma |
| `HELPDESK_ALLOWED_EMAIL_DOMAINS` | `alita.id` | Kosong = semua domain email pemohon diterima |
| `HELPDESK_MAX_UPLOAD_KB` | `5120` | Batas lampiran (JPG, PNG, PDF) |
| `HELPDESK_TRACKING_LINK_DAYS` | `30` | Masa berlaku link tracking di email |
| `HELPDESK_AUTO_CLOSE_DAYS` | `3` | Tiket "selesai" tanpa balasan ditutup otomatis setelah N hari |

Target SLA per prioritas ada di `config/helpdesk.php` (dalam menit, jam kalender).

Contoh SMTP Microsoft 365:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=helpdesk@alita.id
MAIL_PASSWORD=isi-password-aplikasi
MAIL_FROM_ADDRESS="helpdesk@alita.id"
```

Rahasia (password DB dan SMTP) hanya disimpan di `.env`, dan `.env` tidak pernah di-commit.

## Deploy ke server

Kebutuhan server: PHP 8.2+ dengan ekstensi `pdo_pgsql`, `pgsql`, `mbstring`, `intl`, `fileinfo`, `openssl`; PostgreSQL; Composer; Nginx atau Apache dengan document root ke folder `public/`; HTTPS.

```bash
git clone <repo> /var/www/alita-helpdesk && cd /var/www/alita-helpdesk
composer install --no-dev --optimize-autoloader
cp .env.example .env            # isi nilai production (tabel di atas)
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=HelpdeskSeeder --force   # hanya master data, tanpa tiket contoh
php artisan helpdesk:make-admin                        # admin pertama, password diketik interaktif

php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Lampiran disimpan privat di `storage/app/private` dan hanya bisa diunduh lewat aplikasi (panel admin atau signed URL pemohon). **Jangan** menjalankan `php artisan storage:link`: aplikasi ini tidak butuh folder penyimpanan publik.

Di production aplikasi memaksa skema `https` untuk semua URL yang dibuatnya. Link tracking memakai tanda tangan relatif (path + query), jadi tetap valid di balik proxy HTTPS.

**Kalau server berada di balik reverse proxy atau load balancer**, daftarkan IP proxy di `bootstrap/app.php`, misalnya `$middleware->trustProxies(at: ['10.0.0.10']);`. Tanpa itu, semua pemohon terlihat berasal dari IP proxy: rate limit per IP (5 tiket per menit) jadi berlaku untuk seluruh kantor sekaligus, dan IP di audit log tidak akurat.

### Cron (scheduler)

Satu baris crontab untuk user web server:

```cron
* * * * * cd /var/www/alita-helpdesk && php artisan schedule:run >> /dev/null 2>&1
```

Terjadwal: `helpdesk:auto-close` setiap hari pukul 01.00 (Asia/Jakarta). Perintah ini bisa juga dijalankan manual kapan saja.

### Supervisor (queue worker)

Semua email dikirim oleh worker. Tanpa worker, email berhenti di status "Antri". Contoh `/etc/supervisor/conf.d/alita-helpdesk-worker.conf`:

```ini
[program:alita-helpdesk-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/alita-helpdesk/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
directory=/var/www/alita-helpdesk
user=www-data
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=/var/www/alita-helpdesk/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start "alita-helpdesk-worker:*"
```

Email yang gagal dicoba ulang otomatis 3 kali (jeda 1 menit, lalu 5 menit). Kalau tetap gagal, status di **Email log** menjadi "Gagal". Setelah SMTP diperbaiki, tekan **Kirim ulang** di halaman tersebut.

### Update aplikasi

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart       # worker memuat kode baru
php artisan up
```

Naikkan `APP_ASSET_VERSION` di `.env` (lalu `php artisan config:cache`) kalau ada perubahan CSS/JS.

### Backup

Backup database harian di luar aplikasi, misalnya:

```cron
0 2 * * * pg_dump -U alita -Fc alita_helpdesk > /backup/alita_helpdesk_$(date +\%F).dump
```

Ikut sertakan folder `storage/app/private` (lampiran) dalam backup file.

## Admin dan user

- **Admin pertama:** `php artisan helpdesk:make-admin`. Password diketik interaktif, minimal 10 karakter, dan tidak disimpan di `.env`.
- **User lain:** dibuat admin di **Admin → User**. Kalau password awal dikosongkan, user menerima link untuk membuat password sendiri.
- **Tidak ada fitur hapus user,** hanya nonaktifkan. Sistem selalu menyisakan minimal satu admin aktif.

## Pengembangan

```bash
php artisan test                  # memakai database PostgreSQL alita_helpdesk_test (lihat phpunit.xml)
./vendor/bin/pint                 # format kode
./vendor/bin/phpstan analyse      # static analysis level 6
```

Aturan singkat:
- Controller tipis; alurnya Form Request → Policy → Action → Model.
- Tidak ada paket composer/npm baru tanpa persetujuan.
- Tidak ada script/style inline, karena CSP melarangnya dan `tests/Feature/SecurityTest.php` akan gagal.

## Keamanan

- Header keamanan dan Content-Security-Policy di semua halaman (`app/Http/Middleware/SecurityHeaders.php`): hanya aset sendiri dan Google Fonts.
- Pemohon mengakses tiketnya lewat signed URL yang berlaku 30 hari. Halaman `/lacak` tidak membocorkan ada atau tidaknya data.
- Rate limit:
  - kirim tiket: 5 per menit per IP
  - cari tiket: 5 per menit
  - balasan pemohon: 10 per jam per tiket
  - login: 5 per menit per email + IP
  - data antrian: 60 per menit
- Audit log bersifat append-only dan tidak pernah berisi password, token, atau isi lampiran.

## Troubleshooting

| Gejala | Penyebab / solusi |
| --- | --- |
| Email tidak pernah terkirim, status "Antri" | Worker tidak jalan: cek `supervisorctl status` |
| Status "Gagal" di email log | Cek detail error di email log. Perbaiki `MAIL_*`, `php artisan config:cache`, `php artisan queue:restart`, lalu **Kirim ulang** |
| Tiket "selesai" tidak pernah tertutup otomatis | Cron `schedule:run` belum dipasang |
| `could not find driver` | Aktifkan ekstensi `pdo_pgsql` dan `pgsql` di `php.ini` |
| Halaman 419 atau "Halaman terlalu lama dibuka" | Session habis (120 menit). Isian form tetap tersimpan, cukup kirim ulang |
| Link tracking di email "tidak berlaku" | Link lewat 30 hari atau `APP_KEY` berubah. Pemohon bisa meminta link baru di `/lacak` |
| Pemohon sering kena "Terlalu banyak percobaan" | Server di balik proxy tanpa `trustProxies`, lihat bagian Deploy |
| CSS/JS lama masih tampil | Naikkan `APP_ASSET_VERSION`, lalu `php artisan config:cache` |
