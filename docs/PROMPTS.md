# Prompt siap pakai — Alita IT Helpdesk

Salin prompt di bawah ke Claude Code satu per satu. Tunggu laporan agent, cek hasilnya, baru lanjut ke prompt berikutnya.

---

## 0. Persiapan (sekali saja)

Langkah lengkap (PostgreSQL, Laragon, Claude Code) ada di `SETUP.md`. Ringkasnya:

1. Buat project: `composer create-project laravel/laravel:^12.0 alita-helpdesk` lalu masuk ke foldernya.
2. Ekstrak paket ini ke root project (CLAUDE.md, SETUP.md, .env.example, docs/, .claude/, _referensi/, public/css/tokens.css, public/js/motion.js).
3. `copy .env.example .env`, isi `DB_PASSWORD`, `php artisan key:generate`, `php artisan migrate`.
4. `git init && git add . && git commit -m "chore: init + agent kit"`
5. Buka Claude Code di folder project, lalu pasang skill:

```text
/plugin marketplace add DietrichGebert/ponytail
/plugin install ponytail@ponytail
```

```bash
npx skills add https://github.com/Leonxlnx/taste-skill --skill "design-taste-frontend"
```

6. Restart Claude Code, lalu jalankan prompt "Kickoff".

---

## Kickoff (cek pemahaman, belum menulis kode)

```text
Baca CLAUDE.md, docs/PRD.md, docs/BACKEND_RULES.md, dan docs/FRONTEND_RULES.md sampai selesai.
Jangan menulis kode dulu.

Laporkan dalam bahasa Indonesia:
1. Ringkasan aplikasi dalam 5 kalimat.
2. Daftar milestone M0–M7 dengan 1 kalimat per milestone.
3. Konflik atau hal yang tidak jelas antar dokumen, beserta usulan keputusan paling sederhana.
4. Apakah kode Tahap 1 sudah ada di repo, dan apa yang perlu disesuaikan (misalnya warna lama biru/hijau ke oranye Alita).
```

---

## M0 + M1: setup dan database

```text
Kerjakan Milestone M0 dan M1 dari docs/PRD.md.
Ikuti docs/BACKEND_RULES.md dan skill alita-laravel. Gunakan disiplin ponytail: pakai fitur bawaan Laravel, jangan tambah paket selain larastan.

Wajib:
- Skema database persis seperti PRD bagian "Skema database" (nama tabel, kolom, tipe, index, foreign key).
- Enum dengan label, badgeClass, dan aturan transisi di enum.
- Factory + HelpdeskSeeder idempotent + perintah helpdesk:make-admin (password diketik interaktif).
- config/helpdesk.php sesuai BACKEND_RULES §16, lang/id/*.
- Database PostgreSQL (DB_CONNECTION=pgsql). Atur phpunit.xml memakai database alita_helpdesk_test.
- Pastikan config/app.php memakai env('APP_TIMEZONE', 'UTC') dan env('APP_LOCALE', 'en') agar nilai di .env terbaca.
- Jika ada folder _referensi/tahap1, pakai sebagai contoh saja; sesuaikan semua ke PRD.

Setelah selesai jalankan: php artisan migrate:fresh --seed, php artisan test, ./vendor/bin/pint, ./vendor/bin/phpstan analyse.
Perbaiki sampai semua lolos. Commit "feat(m0-m1): setup dan database".
Laporkan: file yang dibuat, asumsi, dan hal yang belum.
```

---

## M2: form tiket + email

```text
Kerjakan Milestone M2 dari docs/PRD.md.
Backend ikuti skill alita-laravel. Tampilan ikuti skill alita-ui dan taste-skill dengan dial
DESIGN_VARIANCE=6, MOTION_INTENSITY=7, VISUAL_DENSITY=4.

Tampilan:
- Warna utama oranye Alita dari public/css/tokens.css. Jika ada kode Tahap 1 berwarna biru/hijau, ganti ke token oranye.
- Hero kiri memakai .aurora; kartu kategori ITApps/ITInfra memakai .glow-border .lift (momen wow halaman ini).
- ITInfra → dropdown Layanan, ITApps → dropdown Modul, Others → input teks (.reveal-in).
- Tombol Kirim tiket: primary + .shine. Responsif 360–1440px.
- Template email HTML sesuai PRD, garis atas oranye.

Wajib test: TicketSubmissionTest, TicketEmailTest, QueuePositionTest.
Jalankan test, pint, phpstan sampai lolos, lalu /ponytail-review pada diff dan bereskan temuan yang valid.
Commit "feat(m2): form tiket dan email". Laporkan hasil + screenshot deskriptif halaman di 390px dan 1280px.
```

---

## M3: papan antrian + tracking

```text
Kerjakan Milestone M3 dari docs/PRD.md dengan skill alita-laravel dan alita-ui
(taste-skill dial 6/7/4).

Perhatikan:
- /antrian/data tidak boleh berisi nama, email, deskripsi, atau nama agent (buat test untuk ini).
- Polling 30 detik di queue-board.js, berhenti saat tab tersembunyi, backoff saat gagal, baris baru .flash-new, header .live-dot.
- Tracking via signed URL, lookup /lacak dengan pesan layar yang selalu sama.
- Balasan pemohon memindahkan menunggu/selesai ke diproses; batal hanya saat baru.

Wajib test: QueueBoardTest, TrackingTest.
Jalankan test, pint, phpstan, /ponytail-review. Commit "feat(m3): antrian dan tracking". Laporkan.
```

---

## M4: autentikasi admin

```text
Kerjakan Milestone M4 dari docs/PRD.md (bagian "Peran dan autentikasi" dan "Panel admin > Login dan akun").
Tanpa Breeze/Jetstream: controller auth sendiri, password broker bawaan Laravel.
Layout admin dengan sidebar + drawer mobile sesuai FRONTEND_RULES (dial 3/4/7), halaman login dengan .aurora--dark di sisi kiri.

Wajib test: AdminAuthTest (login, gagal, nonaktif, throttle, reset password, redirect tamu).
Jalankan test, pint, phpstan, /ponytail-review. Commit "feat(m4): autentikasi admin". Laporkan.
```

---

## M5: pengelolaan tiket

```text
Kerjakan Milestone M5 dari docs/PRD.md.
Actions: ChangeTicketStatus (pakai TicketStatus::canTransitionTo), ChangePriority, AssignTicket, AddComment.
TicketPolicy sesuai matriks hak akses di PRD. Timeline gabungan status + komentar + email.
Tampilan admin padat dan cepat (dial 3/4/7), tanpa efek loop di halaman daftar dan detail.
Daftar tiket jadi kartu di ≤ 767px.

Wajib test: TicketStatusTest, TicketAssignmentTest, AttachmentAccessTest.
Jalankan test, pint, phpstan, /ponytail-review. Commit "feat(m5): pengelolaan tiket". Laporkan.
```

---

## M6: dashboard, master data, user, log

```text
Kerjakan Milestone M6 dari docs/PRD.md.
Dashboard: header .aurora tipis, kartu statistik (satu .stat--brand, sisanya .spotlight .lift) dengan data-count-to,
grafik batang SVG dari server tanpa library, refresh 60 detik via JSON dengan AlitaMotion.countUp.
Master data, user, audit log, email log sesuai PRD, termasuk aturan "minimal satu admin aktif" di Action.

Wajib test: AdminAccessTest, MasterDataTest, UserManagementTest.
Jalankan test, pint, phpstan, /ponytail-review. Commit "feat(m6): dashboard dan master data". Laporkan.
```

---

## M7: scheduler, keamanan, rilis

```text
Kerjakan Milestone M7 dari docs/PRD.md.
- helpdesk:auto-close terjadwal 01.00 + AutoCloseTest.
- Middleware SecurityHeaders dengan CSP; pastikan console browser tanpa error CSP di semua halaman.
- Halaman error 403/404/419/429/500 bergaya sama.
- README lengkap: instalasi, .env, cron, contoh konfigurasi Supervisor, admin pertama.
Lalu jalankan seluruh acceptance criteria manual di PRD dan laporkan mana yang lolos/gagal.
Commit "feat(m7): rilis".
```

---

## Prompt pendukung

### Poles tampilan satu halaman

```text
Pakai taste-skill (dial publik 6/7/4 atau admin 3/4/7) dan skill alita-ui untuk mengaudit halaman <nama halaman>.
Jangan ubah backend. Laporkan dulu 5 masalah terbesar (hierarki, spasi, kontras, responsif, gerak),
lalu perbaiki dengan token yang ada di tokens.css. Tetap patuhi anggaran animasi dan CSP di FRONTEND_RULES §7 dan §10.
Cek ulang checklist FRONTEND_RULES §12.
```

### Review kode (setelah milestone)

```text
Jalankan /ponytail-review pada semua perubahan sejak commit terakhir milestone sebelumnya.
Terapkan hanya temuan yang tidak menghapus validasi, otorisasi, rate limit, audit log, atau test.
Lalu lakukan review keamanan memakai checklist BACKEND_RULES §15 dan laporkan hasilnya per poin.
```

### Perbaiki bug

```text
Bug: <jelaskan apa yang terjadi, langkah reproduksi, dan hasil yang diharapkan>.
Tulis dulu test yang gagal untuk bug ini, lalu perbaiki dengan perubahan sekecil mungkin.
Jalankan seluruh test. Commit "fix(<area>): <ringkasan>".
```

### Lanjutkan pekerjaan yang terputus

```text
Baca CLAUDE.md dan cek git log serta git status. Tentukan milestone yang sedang dikerjakan dan
item mana yang belum selesai di docs/PRD.md. Lanjutkan dari situ tanpa mengulang yang sudah ada.
```

### Tambah fitur di luar PRD

```text
Saya ingin menambah: <fitur>. Jangan menulis kode dulu.
Usulkan perubahan ke docs/PRD.md (skema, routes, halaman, test) dengan perubahan paling kecil,
sebutkan dampaknya ke fitur lain. Setelah saya setujui, perbarui PRD lalu kerjakan.
```
