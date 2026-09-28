# Deploy IT Helpdesk Alita ke `ticketing.alita.id`

Panduan ini untuk admin server 10.0.5.186 dan pemilik repo GitHub. Setup langkah 1–9 cukup dilakukan **sekali**; setelah itu setiap push ke `main` otomatis dites lalu di-deploy.

## Gambaran

```
push ke main ──► GitHub Actions: job "test" (runner GitHub)
                 pint · phpstan · 140+ test dengan PostgreSQL
                        │ lolos
                        ▼
                 job "deploy" (self-hosted runner DI server 10.0.5.186, label "ticketing")
                 deploy/deploy.sh:
                   releases/<waktu>-<commit>  ← kode + composer install --no-dev
                   migrate --force · seeder master · optimize
                   current ──► rilis baru · queue:restart · cek /up (gagal = rollback otomatis)

pengguna ──HTTPS──► WAF Sophos (ticketing.alita.id) ──HTTP :80──► nginx 10.0.5.186 ──► php8.4-fpm
```

Server ada di jaringan internal, jadi GitHub tidak bisa masuk ke server. Sebaliknya, runner di server yang **menarik** job dari GitHub lewat koneksi keluar HTTPS. Tidak perlu membuka port SSH ke internet.

Server ini juga menjalankan AiBAS. Semua langkah di bawah memakai folder, vhost nginx, database, service, dan user sendiri, sehingga AiBAS tidak tersentuh. Jangan mengubah `php` default server (`update-alternatives`) dan jangan menyunting vhost AiBAS.

Struktur di server:

```
/var/www/ticketing/
├── current -> releases/20260928101500-abc1234   (document root: current/public)
├── releases/                                    (5 rilis terakhir, untuk rollback)
└── shared/
    ├── .env                                     (rahasia production, tidak ada di Git)
    └── storage/                                 (lampiran, log, cache)
```

## 1. Keamanan repo (wajib sebelum memasang runner)

Self-hosted runner menjalankan kode dari repo **di server production**. Jadi:

- Jadikan repo **private**: GitHub → Settings → General → Danger Zone → Change visibility.
- Jika harus publik: Settings → Actions → General → "Fork pull request workflows from outside collaborators" → **Require approval for all outside collaborators**. Workflow di repo ini sudah membatasi job deploy hanya untuk `main` di repo ini, tapi PR dari fork tetap bisa mengubah file workflow-nya sendiri.
- Settings → Branches → tambahkan aturan untuk `main` (wajib lewat PR / status check "Test" lolos) jika lebih dari satu orang yang push.

## 2. Prasyarat server

Cek dulu apa yang sudah terpasang (tidak mengubah apa pun):

```bash
php8.4 -v; ls /run/php/            # butuh php8.4-fpm.sock
composer --version; git --version; rsync --version | head -1; curl --version | head -1
psql --version; systemctl is-active postgresql
nginx -v
```

Yang belum ada, pasang **berdampingan** dengan versi PHP lain (contoh Ubuntu, repo PHP ondrej):

```bash
sudo add-apt-repository ppa:ondrej/php && sudo apt update
sudo apt install php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-intl \
                 php8.4-curl php8.4-zip php8.4-bcmath rsync git curl
# Composer 2 (jika belum ada): https://getcomposer.org/download/
# PostgreSQL (jika belum ada): sudo apt install postgresql
```

## 3. User deploy, database, dan folder aplikasi

```bash
# User khusus untuk runner dan deploy, anggota grup web server
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG www-data deploy
sudo mkdir -p /var/www/ticketing && sudo chown deploy:www-data /var/www/ticketing

# Database sendiri (ganti PASSWORD_KUAT)
sudo -u postgres psql -c "CREATE USER alita WITH PASSWORD 'PASSWORD_KUAT';"
sudo -u postgres psql -c "CREATE DATABASE alita_helpdesk OWNER alita;"
```

Buat struktur folder dan `.env` production (APP_KEY dibuat otomatis):

```bash
sudo -iu deploy
git clone https://github.com/Rioagusto1808/it-helpdesk-alita.git ~/it-helpdesk-alita
APP_DIR=/var/www/ticketing PHP_BIN=/usr/bin/php8.4 bash ~/it-helpdesk-alita/deploy/setup-server.sh
nano /var/www/ticketing/shared/.env
```

Di `shared/.env`, isi minimal:

| Variabel | Isi |
| --- | --- |
| `DB_PASSWORD` | password user `alita` dari langkah di atas |
| `MAIL_PASSWORD` | password akun `aibas.notification@alita.id`, **wajib dalam tanda kutip** karena berisi `@` dan `#` |
| `HELPDESK_ADMIN_EMAIL` | penerima email tiket baru, pisahkan dengan koma |
| `TRUSTED_PROXIES` | IP Sophos yang meneruskan request (lihat langkah 8) |

Setting email sudah terisi untuk SMTP SSL: `MAIL_SCHEME=smtps`, `mail.alita-indonesia.com`, port `465`. Login SMTP dengan akun ini sudah dicek berhasil.

## 4. Nginx

```bash
sudo cp ~deploy/it-helpdesk-alita/deploy/nginx/ticketing.alita.id.conf /etc/nginx/sites-available/
sudo ln -s /etc/nginx/sites-available/ticketing.alita.id.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx   # reload tidak memutus AiBAS; jangan reload jika -t gagal
```

Sesuaikan `fastcgi_pass` jika nama socket PHP 8.4 berbeda (`ls /run/php/`). Sampai deploy pertama, domain ini masih 404/502 karena `current` belum ada. Itu normal.

## 5. Queue worker (pengirim email) dan scheduler

```bash
sudo cp ~deploy/it-helpdesk-alita/deploy/systemd/ticketing-queue.service /etc/systemd/system/
sudo cp ~deploy/it-helpdesk-alita/deploy/cron/ticketing /etc/cron.d/ticketing
sudo chmod 0644 /etc/cron.d/ticketing
sudo systemctl daemon-reload
sudo systemctl enable ticketing-queue   # dinyalakan setelah deploy pertama (langkah 9)
```

Tanpa worker, semua email hanya berstatus "Antri". Scheduler menutup otomatis tiket "Selesai" yang tidak dibalas setelah 3 hari (setiap pukul 01.00).

## 6. Self-hosted runner GitHub Actions

1. GitHub → repo → **Settings → Actions → Runners → New self-hosted runner** → Linux, x64.
2. Jalankan perintah "Download" yang ditampilkan GitHub sebagai user `deploy` di `~/actions-runner`.
3. Saat "Configure", tambahkan label dan nama:

   ```bash
   ./config.sh --url https://github.com/Rioagusto1808/it-helpdesk-alita --token <TOKEN_DARI_GITHUB> \
               --name ticketing-prod --labels ticketing --unattended
   ```

4. Jadikan service agar hidup setelah reboot:

   ```bash
   sudo ./svc.sh install deploy && sudo ./svc.sh start
   ```

Runner butuh akses keluar HTTPS ke `github.com` dan `*.actions.githubusercontent.com`. Jika server wajib lewat proxy kantor, isi `https_proxy` di `~/actions-runner/.env`. Pastikan user `deploy` bisa menjalankan `php8.4` dan `composer`.

## 7. GitHub environment (opsional, disarankan)

Settings → Environments → **production**: centang **Required reviewers** jika setiap deploy harus disetujui dulu. Tanpa ini, push ke `main` langsung deploy.

## 8. WAF Sophos

Arahkan virtual webserver `ticketing.alita.id` ke real webserver **10.0.5.186 port 80** dengan header `Host` diteruskan (`ticketing.alita.id`) dan header `X-Forwarded-For`/`X-Forwarded-Proto`. Saat ini domain masih menjawab 403 dari WAF, tanda WAF belum meneruskan ke server.

Setelah itu, lihat IP yang datang di log dan masukkan ke `TRUSTED_PROXIES` di `shared/.env`:

```bash
sudo tail -n 5 /var/log/nginx/ticketing.access.log   # kolom pertama = IP Sophos
```

Tanpa `TRUSTED_PROXIES`, semua pemohon terlihat ber-IP Sophos: batas 5 tiket per menit berlaku untuk seluruh kantor sekaligus dan IP di audit log tidak akurat. Setelah mengubah `.env`, jalankan deploy ulang (Actions → CI/CD → Run workflow) agar cache config diperbarui.

## 9. Deploy pertama

1. Push ke `main`, atau GitHub → Actions → **CI/CD → Run workflow** (branch `main`).
2. Setelah job "Deploy production" hijau:

   ```bash
   sudo systemctl start ticketing-queue
   cd /var/www/ticketing/current && php8.4 artisan helpdesk:make-admin   # admin pertama
   curl -H "Host: ticketing.alita.id" http://127.0.0.1/up                 # harus 200
   ```

3. Buka https://ticketing.alita.id, buat tiket uji, lalu cek di **Admin → Email log** bahwa status email menjadi "Terkirim".

Database production dimulai kosong. Deploy hanya mengisi data master (kategori ITApps/ITInfra, layanan, modul "Others"); tiket contoh tidak pernah dibuat di production.

## Sehari-hari

| Kebutuhan | Caranya |
| --- | --- |
| Rilis perubahan | Merge/push ke `main`. Test jalan dulu; kalau merah, deploy tidak jalan. |
| Deploy ulang (mis. setelah ubah `.env`) | Actions → CI/CD → Run workflow → `main` |
| Rollback | Paling aman: `git revert <commit>` lalu push. Darurat: `ln -sfn /var/www/ticketing/releases/<rilis-lama> /var/www/ticketing/current && php8.4 /var/www/ticketing/current/artisan queue:restart` |
| Lihat log aplikasi | `/var/www/ticketing/shared/storage/logs/laravel-YYYY-MM-DD.log` |
| Status worker | `systemctl status ticketing-queue` · `journalctl -u ticketing-queue -n 50` |
| Maintenance mode | `php8.4 /var/www/ticketing/current/artisan down` / `up` |

Rollback otomatis di `deploy.sh` hanya memindahkan kode. Migrasi database tidak di-rollback, jadi migrasi baru harus tetap kompatibel dengan kode rilis sebelumnya (tambah kolom nullable dulu, hapus kolom di rilis berikutnya).

## Backup

```cron
# crontab user postgres: dump harian, simpan 14 hari
0 2 * * * pg_dump -Fc alita_helpdesk > /backup/ticketing/db_$(date +\%F).dump && find /backup/ticketing -name 'db_*' -mtime +14 -delete
```

Ikut sertakan `/var/www/ticketing/shared/storage/app/private` (lampiran) dan `/var/www/ticketing/shared/.env` dalam backup file.

## Troubleshooting

| Gejala | Penyebab umum |
| --- | --- |
| Job deploy "Waiting for a runner" | Runner mati: `sudo ~deploy/actions-runner/svc.sh status`, atau label bukan `ticketing` |
| Deploy gagal di "Composer install" | `composer` tidak ada di PATH user `deploy`, atau PHP bukan 8.4 (`PHP_BIN` di workflow) |
| Deploy gagal di health check | Vhost nginx belum aktif, socket php-fpm salah, atau `.env` salah; lihat `ticketing.error.log` dan `laravel-*.log`. Rilis lama otomatis dipakai lagi |
| 403 dari WAF / domain tidak terbuka | Sophos belum meneruskan `ticketing.alita.id` ke 10.0.5.186:80 |
| 502 Bad Gateway | `php8.4-fpm` mati atau path socket salah |
| Email "Antri" terus | `ticketing-queue` tidak jalan |
| Email "Gagal" | Password SMTP salah/kedaluwarsa. Perbaiki `.env`, deploy ulang, lalu **Kirim ulang** di Email log |
| Link email ke `http://` atau semua IP sama | `TRUSTED_PROXIES` belum diisi (langkah 8) |
| Tampilan lama setelah deploy | Tidak terjadi: versi aset = hash commit; hard refresh jika WAF meng-cache HTML |
