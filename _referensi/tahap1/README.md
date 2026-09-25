# IT Helpdesk Alita — Tahap 1 (form tiket + email)

Tahap ini berisi: database lengkap, form tiket (ITPass → Module, ITInfra → Services),
upload lampiran aman, penomoran tiket, riwayat status, audit log, dan email otomatis
ke admin (rio@alita.id) serta ke pemohon lewat queue.

## Kebutuhan
- PHP 8.2+, Composer, MySQL/MariaDB
- Laravel 11 atau 12

## Instalasi

1. Buat project baru lalu salin semua folder dari paket ini ke dalamnya (timpa `routes/web.php`):

   ```bash
   composer create-project laravel/laravel helpdesk-it
   ```

2. Atur `.env`:

   ```env
   APP_NAME="IT Helpdesk Alita"
   APP_TIMEZONE=Asia/Jakarta
   APP_URL=https://helpdesk.alita.id

   DB_CONNECTION=mysql
   DB_DATABASE=helpdesk_it
   DB_USERNAME=...
   DB_PASSWORD=...

   QUEUE_CONNECTION=database

   # Sesuaikan dengan server email kantor.
   # Contoh Microsoft 365: smtp.office365.com / 587 / tls
   # Contoh Google Workspace: smtp.gmail.com / 587 / tls (pakai App Password)
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.office365.com
   MAIL_PORT=587
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=helpdesk@alita.id
   MAIL_PASSWORD=...
   MAIL_FROM_ADDRESS=helpdesk@alita.id
   MAIL_FROM_NAME="IT Helpdesk Alita"

   HELPDESK_NAME="IT Helpdesk Alita"
   HELPDESK_ADMIN_EMAIL=rio@alita.id   # boleh lebih dari satu: rio@alita.id,it@alita.id
   ```

   Di Laravel 11/12 timezone diatur lewat `APP_TIMEZONE`; di versi lama, ubah `timezone` di `config/app.php`.

3. Jalankan migration dan seeder:

   ```bash
   php artisan migrate
   php artisan db:seed --class=HelpdeskSeeder
   ```

   Isi dulu nama modul ITPass di `database/seeders/HelpdeskSeeder.php` (bagian `$modules`).

4. Jalankan worker queue (wajib, tanpa ini email tidak terkirim):

   ```bash
   php artisan queue:work --tries=3
   ```

   Di server production, jalankan lewat Supervisor supaya worker hidup terus.

5. Buka `http://localhost:8000` (`php artisan serve`) dan coba kirim tiket.
   Untuk uji email tanpa SMTP asli, set `MAIL_MAILER=log` lalu cek `storage/logs/laravel.log`.

## Isi paket

| Lokasi | Fungsi |
|---|---|
| `database/migrations/…_create_helpdesk_tables.php` | Semua tabel: categories, services, modules, tickets, attachments, comments, status histories, activity logs, email logs, kolom role di users |
| `database/seeders/HelpdeskSeeder.php` | Data awal ITPass/ITInfra, services (Laptop, Printer, Internet, Email, Others), modules |
| `app/Enums/TicketStatus.php` | Status tiket: baru, diproses, menunggu, selesai, ditutup, dibatalkan |
| `app/Models/*` | Model + relasi, posisi antrian, label layanan/modul |
| `app/Observers/TicketObserver.php` | Nomor tiket otomatis + catat riwayat status |
| `app/Http/Requests/StoreTicketRequest.php` | Validasi server (kondisional per kategori, honeypot, file) |
| `app/Http/Controllers/TicketController.php` | Tampilkan form, simpan tiket, halaman sukses |
| `app/Support/TicketNotifier.php`, `app/Jobs/SendTicketEmail.php` | Antrian email + catatan terkirim/gagal di `email_logs` |
| `app/Mail/*`, `resources/views/emails/tickets/*` | Template email admin & pemohon |
| `resources/views/layouts`, `resources/views/tickets` | Tampilan Blade |
| `public/css/helpdesk.css`, `public/js/helpdesk.js` | Desain dan interaksi form |

## Keamanan yang sudah diterapkan
- CSRF token di form, validasi ulang di server untuk semua field
- Rate limit 5 submit/menit per IP, honeypot anti-bot, cegah double submit
- Lampiran: whitelist JPG/PNG/PDF, maks 5 MB, disimpan di disk privat dengan nama acak, tidak dilampirkan ke email
- Isi tiket di-escape di halaman dan email (anti XSS / HTML injection)
- Halaman sukses hanya bisa dibuka lewat session setelah submit, jadi nomor tiket tidak bisa ditebak untuk mengintip data
- Kredensial SMTP hanya di `.env`

## Tahap berikutnya
- Papan antrian publik `/queue`
- Halaman tracking pemohon via signed URL + balasan
- Login admin, dashboard, ubah status/assign/prioritas, komentar internal
- Halaman audit log & email log, CRUD master modul/layanan
- Email notifikasi saat status berubah
