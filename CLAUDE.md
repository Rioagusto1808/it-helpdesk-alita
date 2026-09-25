# Alita IT Helpdesk — panduan untuk agent

Aplikasi web Laravel untuk melapor kendala IT (ITPass → Module, ITInfra → Services), antrian transparan, dan panel admin tim IT. Pemohon tanpa login; admin/agent login. Database PostgreSQL. Email tiket baru ke `HELPDESK_ADMIN_EMAIL` (default rio@alita.id).

## Sumber kebenaran (baca sesuai kebutuhan, jangan menebak)

| File | Isi | Kapan dibaca |
| --- | --- | --- |
| `docs/PRD.md` | apa yang dibangun: skema DB, aturan bisnis, halaman, routes, email, milestone | selalu, di awal setiap milestone |
| `docs/BACKEND_RULES.md` | cara menulis PHP/Laravel | sebelum menyentuh `app/`, `database/`, `routes/`, `tests/` |
| `docs/FRONTEND_RULES.md` | warna oranye Alita, komponen, animasi, responsif | sebelum menyentuh `resources/views/`, `public/css/`, `public/js/` |
| `public/css/tokens.css` | token desain + kelas animasi siap pakai | setiap kali menulis CSS |
| `docs/snippets/components-demo.html` | contoh visual komponen | saat membuat komponen UI |
| `docs/PROMPTS.md` | prompt per milestone | untuk manusia; agent cukup mengikuti PRD |
| `SETUP.md` | instalasi lokal (PostgreSQL, Laragon, Claude Code) | saat menyiapkan environment |
| `_referensi/tahap1/` | kode lama, hanya contoh (lihat `CATATAN.md`) | saat M2, lalu boleh dihapus |

Urutan prioritas jika bertentangan: keamanan & aksesibilitas → file RULES → PRD → skill.

## Aturan emas

1. Kerjakan **satu milestone** sesuai urutan di PRD. Jangan melompat atau menambah fitur di luar PRD.
2. **Kode minimal (ponytail):** pakai fitur Laravel/browser bawaan sebelum menulis kode baru. Tidak ada paket composer/npm baru tanpa persetujuan. Validasi, otorisasi, rate limit, transaksi, audit log, dan test tidak pernah dipangkas.
3. **Tampilan berkualitas (taste-skill):** ikuti `FRONTEND_RULES.md`; efek dibuat dengan CSS + JavaScript vanilla, bukan React/Tailwind/GSAP.
4. Controller tipis → Form Request → Policy → Action → Model. Logika bisnis hanya di Action.
5. Warna, spasi, radius, durasi hanya dari `tokens.css`. Oranye brand `#E97537`; teks putih hanya di atas `--brand-700` ke atas.
6. Tanpa inline `<style>`, `<script>`, `style=""`, `onclick=""` (CSP).
7. Teks UI bahasa Indonesia, sentence case, tombol berupa kata kerja.
8. Setiap fitur punya feature test. Milestone belum selesai kalau test merah.
9. Kalau ragu: pilih opsi paling sederhana yang aman, catat sebagai asumsi di laporan, lanjutkan.

## Perintah

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan helpdesk:make-admin           # admin pertama
php artisan serve                         # http://localhost:8000
php artisan queue:work --tries=3          # wajib untuk email
php artisan schedule:work                 # auto-close (local)

php artisan test                          # semua test
./vendor/bin/pint                         # format
./vendor/bin/phpstan analyse              # static analysis level 6
```

## Skill yang dipakai

- **ponytail** (DietrichGebert/ponytail): disiplin kode minimal. Setelah setiap milestone jalankan `/ponytail-review` pada diff.
- **taste-skill** (`design-taste-frontend`, Leonxlnx/taste-skill): kualitas visual. Dial: publik VARIANCE 6 / MOTION 7 / DENSITY 4; admin 3 / 4 / 7.
- Skill proyek di `.claude/skills/alita-ui` dan `.claude/skills/alita-laravel` merangkum aturan proyek ini.

Instalasi (sekali, di Claude Code):

```bash
# ponytail
/plugin marketplace add DietrichGebert/ponytail
/plugin install ponytail@ponytail

# taste-skill (di terminal, dari root project)
npx skills add https://github.com/Leonxlnx/taste-skill --skill "design-taste-frontend"
```

## Selesai milestone = semua ini benar

- [ ] Item milestone di PRD selesai
- [ ] `php artisan test`, `pint --test`, `phpstan analyse` lolos
- [ ] `/ponytail-review` sudah dijalankan dan temuan valid dibereskan
- [ ] Checklist UI (`FRONTEND_RULES.md` §12) dan keamanan (`BACKEND_RULES.md` §15) dicek
- [ ] Commit `feat(mX): ...` + laporan singkat: dibuat apa, asumsi apa, sisa apa
