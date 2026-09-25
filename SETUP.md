# Setup project Alita IT Helpdesk (Windows + Laragon + PostgreSQL)

Ikuti berurutan. Di Mac/Linux perintahnya sama, hanya lokasi folder dan cara memasang PostgreSQL yang berbeda.

## 1. Alat yang dibutuhkan

| Alat | Versi | Cek |
| --- | --- | --- |
| PHP | 8.2 atau lebih baru | `php -v` |
| Composer | 2.x | `composer -V` |
| PostgreSQL | 16 (minimal 15) | `psql --version` |
| Node.js | LTS | `node -v` (untuk memasang taste-skill) |
| Git | terbaru | `git --version` |
| Claude Code | terbaru | `claude --version` |

## 2. Pasang PostgreSQL

1. Unduh installer Windows dari postgresql.org (versi 16), jalankan.
2. Buat password user `postgres` dan catat. Port biarkan 5432.
3. Centang pgAdmin 4 dan Command Line Tools.

## 3. Aktifkan driver PostgreSQL di PHP

Laragon: menu PHP → Extensions → centang `pdo_pgsql` dan `pgsql`.
Atau di `php.ini` hapus titik koma pada `extension=pdo_pgsql` dan `extension=pgsql`.

```powershell
php -m | findstr pgsql      # harus muncul pdo_pgsql dan pgsql
```

## 4. Buat user dan database

```powershell
& "C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres
```

```sql
CREATE USER alita WITH PASSWORD 'ganti_dengan_password_kuat';
CREATE DATABASE alita_helpdesk OWNER alita ENCODING 'UTF8';
CREATE DATABASE alita_helpdesk_test OWNER alita ENCODING 'UTF8';
\q
```

## 5. Buat folder project Laravel

```powershell
cd C:\laragon\www
composer create-project laravel/laravel:^12.0 alita-helpdesk
cd alita-helpdesk
```

## 6. Masukkan paket agent

Ekstrak `alita-helpdesk-agent-kit.zip` langsung ke dalam folder `alita-helpdesk` (timpa jika ditanya). Hasilnya:

```text
alita-helpdesk/
├── .claude/skills/alita-ui/SKILL.md
├── .claude/skills/alita-laravel/SKILL.md
├── _referensi/tahap1/          kode Tahap 1, hanya contoh (baca CATATAN.md di dalamnya)
├── app/ bootstrap/ config/ database/ routes/ ...   bawaan Laravel
├── docs/
│   ├── PRD.md
│   ├── FRONTEND_RULES.md
│   ├── BACKEND_RULES.md
│   ├── PROMPTS.md
│   └── snippets/components-demo.html
├── public/css/tokens.css
├── public/js/motion.js
├── .env.example                 sudah berisi pengaturan PostgreSQL + HELPDESK_*
├── CLAUDE.md
└── SETUP.md                     file ini
```

Folder `.claude` diawali titik; aktifkan View → Show → Hidden items di Explorer untuk melihatnya.

## 7. Siapkan `.env`

```powershell
copy .env.example .env
php artisan key:generate
```

Buka `.env`, isi `DB_PASSWORD` dengan password user `alita`. Sisanya sudah benar.

## 8. Tes koneksi dan jalankan

```powershell
php artisan config:clear
php artisan migrate
php artisan serve
```

Buka http://localhost:8000. Halaman sambutan Laravel berarti setup berhasil. File `database/database.sqlite` boleh dihapus.

## 9. Simpan titik awal di Git

```powershell
git init
git add .
git commit -m "chore: init laravel + agent kit"
```

## 10. Claude Code dan skill

```powershell
irm https://claude.ai/install.ps1 | iex     # pasang Claude Code (tanpa Administrator)
cd C:\laragon\www\alita-helpdesk
claude                                       # login lewat browser saat pertama kali
```

Di dalam Claude Code:

```text
/plugin marketplace add DietrichGebert/ponytail
/plugin install ponytail@ponytail
/exit
```

Di PowerShell (folder project):

```powershell
npx skills add https://github.com/Leonxlnx/taste-skill --skill "design-taste-frontend"
claude
```

## 11. Mulai kerja

1. Salin prompt **Kickoff** dari `docs/PROMPTS.md` ke Claude Code.
2. Setelah laporannya pas, lanjut prompt **M0 + M1**, lalu M2 sampai M7 satu per satu.
3. Selama bekerja, jalankan di terminal lain: `php artisan serve`, dan mulai M2 juga `php artisan queue:work`.

## Kalau ada error

| Pesan | Solusi |
| --- | --- |
| `could not find driver` | aktifkan `pdo_pgsql` (langkah 3), restart terminal/Laragon |
| `password authentication failed` | samakan `DB_USERNAME`/`DB_PASSWORD` di `.env` dengan langkah 4 |
| `Connection refused` | jalankan service `postgresql-x64-16` di `services.msc` |
| `permission denied for schema public` | `psql -U postgres -d alita_helpdesk` lalu `GRANT ALL ON SCHEMA public TO alita;` |
| `claude` / `npx` tidak dikenali | tutup dan buka lagi PowerShell; cek Node.js sudah terpasang |
