# IT Helpdesk Alita

Aplikasi web untuk melapor kendala IT (ITApps → modul, ITInfra → layanan), memantau antrian secara transparan, dan mengelola tiket oleh tim IT.

- **Pemohon** tidak perlu login: kirim tiket di `/`, lihat antrian di `/antrian`, pantau dan balas tiket lewat link di email (atau minta ulang di `/lacak`).
- **Tim IT** login di `/admin`: dashboard, daftar dan detail tiket, master data layanan/modul, user, audit log, dan email log.
- Setiap tiket baru dikirim ke `HELPDESK_ADMIN_EMAIL` (default `rio@alita.id`). Semua email lewat queue dan tercatat di email log.

Spesifikasi lengkap: [docs/PRD.md](docs/PRD.md). Aturan kode: [docs/BACKEND_RULES.md](docs/BACKEND_RULES.md) dan [docs/FRONTEND_RULES.md](docs/FRONTEND_RULES.md).

## Stack

| Komponen | Pilihan |
| --- | --- |
| Bahasa / framework | PHP 8.4+ (diuji di 8.4 dan 8.5), Laravel 12 |
| Database | PostgreSQL 16+ (minimal 15) |
| Tampilan | Blade + CSS + JavaScript vanilla di `public/`, **tanpa npm/Vite/build step** |
| Queue | driver `database`, worker dijalankan systemd (`deploy/systemd`) |
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

Jalankan web, queue worker (pengirim email), dan scheduler sekaligus:

```bash
composer run dev              # http://localhost:8000 + queue:listen + schedule:work
```

Tanpa queue worker, email hanya menumpuk di antrian dan tidak pernah terkirim.

Selama `MAIL_MAILER=log`, email tidak benar-benar dikirim, tapi ditulis ke `storage/logs/laravel.log`.

## Konfigurasi `.env`

| Variabel | Contoh | Keterangan |
| --- | --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | **Wajib** `false` di production |
| `APP_URL` | `https://ticketing.alita.id` | Dipakai di link email dan signed URL tracking |
| `APP_TIMEZONE` / `APP_LOCALE` | `Asia/Jakarta` / `id` | |
| `APP_ASSET_VERSION` | `1` | Versi cache CSS/JS. Di production diisi otomatis dengan hash commit oleh `deploy.sh` |
| `TRUSTED_PROXIES` | `10.0.5.1` | IP WAF/reverse proxy di depan aplikasi (production). Kosong di lokal |
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

SMTP production (SSL port 465):

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=mail.alita-indonesia.com
MAIL_PORT=465
MAIL_USERNAME=aibas.notification@alita.id
MAIL_PASSWORD="isi-password"        # tanda kutip wajib jika ada # atau @
MAIL_FROM_ADDRESS=aibas.notification@alita.id
```

Untuk STARTTLS port 587, pakai `MAIL_SCHEME=null`. `MAIL_ENCRYPTION` tidak dipakai lagi di Laravel 12.

Rahasia (password DB dan SMTP) hanya disimpan di `.env`, dan `.env` tidak pernah di-commit.

## Deploy (CI/CD)

Production: **https://ticketing.alita.id** di server internal 10.0.5.186 (di belakang WAF Sophos).

- Setiap push dan pull request menjalankan `.github/workflows/ci-cd.yml`: Pint, PHPStan, dan semua test dengan PostgreSQL.
- Push ke `main` yang lolos test otomatis di-deploy oleh self-hosted runner di server (`deploy/deploy.sh`): rilis atomik, migrasi, cache, `queue:restart`, health check `/up`, dan rollback otomatis jika gagal.
- Server butuh PHP 8.4 (`php8.4-fpm`, `pdo_pgsql`, `mbstring`, `intl`, `xml`, `curl`, `zip`), Composer 2, PostgreSQL, nginx.

Setup server sekali jalan (user deploy, database, `.env`, nginx, queue worker, cron, runner, WAF) ada di **[DEPLOY.md](DEPLOY.md)**. File konfigurasi siap pakai ada di folder `deploy/`.

Lampiran disimpan privat di `storage/app/private` dan hanya bisa diunduh lewat aplikasi (panel admin atau signed URL pemohon). **Jangan** menjalankan `php artisan storage:link`: aplikasi ini tidak butuh folder penyimpanan publik.

Link tracking memakai tanda tangan relatif (path + query), jadi tetap valid di balik proxy HTTPS. IP proxy didaftarkan lewat `TRUSTED_PROXIES` (lihat [DEPLOY.md](DEPLOY.md#8-waf-sophos)).

Email dikirim oleh queue worker. Tanpa worker, email berhenti di status "Antri". Email yang gagal dicoba ulang otomatis 3 kali; jika tetap gagal, statusnya "Gagal" di **Email log** dan bisa dikirim ulang setelah SMTP diperbaiki.

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
| Email tidak pernah terkirim, status "Antri" | Worker tidak jalan: lokal `composer run dev`, server `systemctl status ticketing-queue` |
| Status "Gagal" di email log | Cek detail error di email log. Perbaiki `MAIL_*`, `php artisan config:cache`, `php artisan queue:restart`, lalu **Kirim ulang** |
| Tiket "selesai" tidak pernah tertutup otomatis | Cron `schedule:run` belum dipasang |
| `could not find driver` | Aktifkan ekstensi `pdo_pgsql` dan `pgsql` di `php.ini` |
| Halaman 419 atau "Halaman terlalu lama dibuka" | Session habis (120 menit). Isian form tetap tersimpan, cukup kirim ulang |
| Link tracking di email "tidak berlaku" | Link lewat 30 hari atau `APP_KEY` berubah. Pemohon bisa meminta link baru di `/lacak` |
| Pemohon sering kena "Terlalu banyak percobaan" | `TRUSTED_PROXIES` belum diisi, lihat DEPLOY.md langkah 8 |
| CSS/JS lama masih tampil | Lokal: naikkan `APP_ASSET_VERSION`. Production: otomatis per commit |
