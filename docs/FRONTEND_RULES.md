# Frontend Rules — Alita IT Helpdesk

Dokumen ini adalah aturan wajib untuk semua tampilan (Blade, CSS, JavaScript).
Kalau ada perbedaan dengan PRD atau dengan default skill mana pun, **dokumen ini yang berlaku**.

- File token dan animasi siap pakai: `public/css/tokens.css`, `public/js/motion.js`
- Contoh visual komponen: `docs/snippets/components-demo.html` (buka di browser)

---

## 0. Prioritas saat ada konflik

1. Aksesibilitas dan keamanan (CSP, kontras, keyboard) selalu menang.
2. Dokumen ini.
3. `docs/PRD.md`.
4. taste-skill (`design-taste-frontend`) untuk kualitas visual: layout, spasi, hierarki, detail interaksi.
5. ponytail untuk jumlah kode: pakai fitur native browser dan CSS sebelum menulis JavaScript atau menambah library.

Cara menggabungkan taste-skill dan ponytail: **tampilan mengikuti taste, implementasi mengikuti ponytail.**
Efek visual boleh kaya, tapi dibuat dengan CSS dan sedikit JavaScript vanilla, tidak dengan library.

Abaikan saran taste-skill yang tidak cocok dengan stack proyek ini: React, Tailwind, Framer Motion, GSAP, Lenis, shadcn, atau build step apa pun. Stack kita adalah Blade + CSS + JavaScript vanilla tanpa npm.

Dial taste-skill untuk proyek ini (sebutkan di prompt saat memakai skill):

| Area | DESIGN_VARIANCE | MOTION_INTENSITY | VISUAL_DENSITY |
| --- | --- | --- | --- |
| Halaman publik (form, sukses, antrian, tracking) | 6 | 7 | 4 |
| Panel admin (dashboard, tabel, detail) | 3 | 4 | 7 |
| Halaman login | 5 | 5 | 3 |

---

## 1. Prinsip desain

1. **Oranye Alita adalah identitas.** Warna brand muncul di elemen yang penting (tombol utama, kategori terpilih, angka antrian, status aktif), bukan di semua tempat. Sisanya netral abu-abu dingin.
2. **Satu momen "wow" per halaman.** Contoh: aurora di hero form, border berputar di kartu kategori terpilih, count-up di dashboard. Jangan menumpuk semua efek di satu layar.
3. **Kejelasan lebih penting dari dekorasi.** Orang yang membuka helpdesk sedang punya masalah. Setiap layar harus langsung menjawab: "apa yang harus saya lakukan?" atau "tiket saya di mana?"
4. **Mobile dulu.** Sebagian besar laporan dikirim dari HP saat laptop bermasalah.
5. **Gerak menjelaskan perubahan.** Animasi dipakai untuk menunjukkan sesuatu muncul, berubah, atau baru; bukan untuk hiasan terus-menerus di area kerja admin.

---

## 2. Warna

Semua warna diambil dari variabel di `tokens.css`. **Dilarang menulis kode hex di file CSS lain atau di Blade.**

| Token | Hex | Boleh untuk | Jangan untuk |
| --- | --- | --- | --- |
| `--brand-500` | #E97537 | aksen, ikon, border aktif, garis, grafik, titik live | teks kecil di atas putih, latar teks putih kecil |
| `--brand-600` | #D8622A | focus ring, border tebal aktif | teks body |
| `--brand-700` | #B9501F | tombol utama, link, angka posisi antrian, teks aksen | latar area besar |
| `--brand-50` / `--brand-100` | #FEF4EE / #FDE6D6 | latar lembut, hover baris, badge "Diproses" | teks |
| `--grad-brand` | #F59A5B → #E97537 → #C2481A | aurora, garis dekoratif, border beranimasi, ilustrasi | latar yang berisi teks putih |
| `--grad-brand-deep` | #C2531B → #8F3D18 | kartu/banner berisi teks putih | |
| `--grad-brand-strong` | #B9501F → #8F3D18 | tombol utama | |
| `--grad-brand-soft` | #FFF8F3 → #FDE6D6 | latar kartu terpilih, panel info | |
| `--ink` / `--ink-2` | #1B1F24 / #343A42 | teks utama, judul | |
| `--muted` | #5F6670 | teks sekunder, label kecil | |
| `--subtle` | #8B929B | placeholder, ikon non-esensial | teks yang harus dibaca |
| `--line` / `--line-strong` | #E3E5E9 / #CDD1D7 | border, pemisah | |
| `--canvas` / `--paper` | #F5F6F8 / #FFFFFF | latar halaman / kartu dan panel | |
| `--infra` / `--apps` (+ `-soft`) | #2563A6 / #0F7A64 | tag kecil kategori saja | tombol, latar besar |

Aturan kontras (WCAG AA):

- Teks normal minimal 4.5:1, teks besar (≥ 24px, atau ≥ 19px bold) dan elemen UI minimal 3:1.
- Teks putih hanya boleh di atas `--brand-700`, `--brand-800`, `--brand-900`, `--grad-brand-deep`, `--grad-brand-strong`, atau `--ink`.
- **Jangan pernah** teks putih di atas `--brand-500` atau `--grad-brand` (kontrasnya hanya ±3:1).
- Status tidak boleh dibedakan dengan warna saja; selalu ada teks label.

Warna status (badge):

| Status | Teks | Latar |
| --- | --- | --- |
| Baru | `--st-baru` | `--st-baru-bg` |
| Diproses | `--st-diproses` | `--st-diproses-bg` |
| Menunggu | `--st-menunggu` | `--st-menunggu-bg` |
| Selesai | `--st-selesai` | `--st-selesai-bg` |
| Ditutup / Dibatalkan | `--st-ditutup` | `--st-ditutup-bg` + border `--line` |
| Lewat SLA | `--danger` | `--danger-bg` |

Prioritas: urgent = teks putih di `--danger` solid; tinggi = `--danger` di `--danger-bg`; sedang = netral; rendah = outline `--line-strong`.

Mode gelap tidak termasuk cakupan versi pertama.

---

## 3. Tipografi

- Satu keluarga: **Plus Jakarta Sans** (400, 500, 700, 800) dari Google Fonts dengan `display=swap`, fallback `system-ui`.
- Skala: `--fs-xs` 12, `--fs-sm` 14, `--fs-base` 16, `--fs-lg` 20, `--fs-xl` 28, `--fs-2xl` 32–40, `--fs-3xl` 36–52 (fluid dengan `clamp`).
- Judul halaman publik: `--fs-3xl`, weight 800, `line-height: 1.05`, `letter-spacing: -.03em`, maksimal 14ch per baris.
- Judul halaman admin: `--fs-xl`, weight 800.
- Label field: `--fs-sm`, weight 700. Teks bantuan: `--fs-sm`, `--muted`.
- Body `line-height: 1.55`; panjang baris maksimal ±70 karakter.
- Angka (nomor tiket, posisi, statistik, waktu): `font-variant-numeric: tabular-nums`.
- Nomor tiket tidak boleh terpotong ke baris baru: `white-space: nowrap`, kecilkan font di layar sempit.
- Sentence case di semua teks. Dilarang label ALL CAPS dan huruf renggang (tracking lebar) di atas judul.
- Minimal ukuran teks yang dibaca: 14px. Input: minimal 16px (mencegah zoom otomatis di iOS).

---

## 4. Spasi, radius, bayangan

- Grid 4px: gunakan `--sp-1` sampai `--sp-16`. Dilarang nilai acak seperti 13px atau 27px.
- Jarak antar field form 24px; antar section 48–64px; padding kartu 20–24px; padding panel utama 24–40px (fluid).
- Radius punya hierarki: `--r-sm` 8px (input, tombol, badge persegi), `--r-md` 12px (kartu), `--r-lg` 20px (panel utama, hero), `--r-pill` (badge status, chip).
- Bayangan: `--sh-1` untuk kartu diam, `--sh-2` untuk hover/terangkat dan dropdown, `--sh-brand` hanya untuk tombol utama. Jangan beri bayangan ke semua kartu sekaligus; kartu biasa cukup border `--line`.

---

## 5. Layout dan responsif

| Breakpoint | Lebar | Perilaku |
| --- | --- | --- |
| xs | 360–539px | 1 kolom, tombol utama full width, kartu kategori bertumpuk, kartu statistik 2 kolom |
| sm | 540–767px | 1 kolom, field Nama/Email berdampingan |
| md | 768–1023px | publik: 1 kolom lebar; admin: sidebar jadi drawer |
| lg | 1024–1279px | publik: 2 kolom (pengantar + form); admin: sidebar 240px |
| xl | ≥ 1280px | konten maksimal 1120px (publik) / 1400px (admin) di tengah |

Aturan:

- Tulis CSS mobile-first: gaya dasar untuk layar kecil, lalu `@media (min-width: …)`.
- Gunakan `grid` dan `flex` dengan `minmax(0, 1fr)` agar teks panjang tidak merusak layout.
- Tidak boleh ada scroll horizontal di level halaman pada lebar 360px. Tabel lebar boleh digeser di dalam wadahnya sendiri (`overflow-x: auto`).
- Di ≤ 767px, tabel tiket admin berubah menjadi daftar kartu (setiap baris = kartu dengan nomor, status, kategori, waktu).
- Target sentuh minimal 44×44px.
- Gambar/ikon memakai `max-width: 100%`. Ikon memakai SVG inline, bukan font ikon atau emoji.
- Uji di 360, 390, 768, 1024, 1280, dan 1440px sebelum menyatakan selesai.

---

## 6. Komponen

Setiap komponen yang dipakai lebih dari sekali dibuat sebagai Blade component di `resources/views/components/`. Kelas CSS memakai pola sederhana `komponen`, `komponen-bagian`, `komponen--varian` (contoh: `stat`, `stat-value`, `stat--brand`).

### 6.1 Tombol (`<x-button>`)

| Varian | Tampilan | Dipakai untuk |
| --- | --- | --- |
| `primary` | latar `--grad-brand-strong`, teks putih, `--sh-brand`, efek `.shine` | satu aksi utama per layar (Kirim tiket, Simpan) |
| `secondary` | latar `--paper`, border `--line`, teks `--ink` | aksi kedua (Batal, Lihat antrian) |
| `ghost` | tanpa border, teks `--brand-700` | aksi ringan di dalam kartu |
| `danger` | latar `--danger`, teks putih | hapus, batalkan tiket |

- Tinggi 48px (publik) / 40px (admin, tabel). Radius `--r-sm`. Font weight 700.
- Hover: naik 1px. Aktif: turun 1px. Fokus: `box-shadow: var(--focus-ring)`.
- Loading: tombol `disabled`, teks berubah ("Mengirim tiket…"), spinner CSS kecil di kiri. Label tombol = kata kerja yang jelas; toast hasilnya memakai kata yang sama ("Tiket terkirim").
- Maksimal satu tombol `primary` per area.

### 6.2 Field form (`<x-field>`)

- Struktur: label → input → teks bantuan → pesan error.
- Input: tinggi 48px, border 1.5px `--line`, radius `--r-sm`, latar `--paper`. Fokus: border `--brand-600` + ring `0 0 0 3px var(--brand-100)`.
- Error: border `--danger`, pesan `--fs-sm` `--danger` di bawah field, `aria-invalid="true"`, `aria-describedby` ke id pesan.
- Select memakai `appearance: none` + ikon chevron SVG (data URI dalam CSS).
- Field bersyarat (Layanan/Modul/Others) muncul dengan kelas `.reveal-in`.
- Upload: dropzone dengan border putus-putus; saat file diseret di atasnya, border `--brand-500` dan latar `--brand-50`. Setelah dipilih, tampil chip nama + ukuran + tombol Hapus.

### 6.3 Kartu kategori (ITApps / ITInfra)

Ini "momen wow" di form.

- Dua kartu radio besar berdampingan (bertumpuk di xs), kelas `.glow-border .lift`.
- Isi: nama kategori (`--fs-lg`, 800), tag kecil (`.tag-infra` "Perangkat" / `.tag-apps` "Aplikasi"), deskripsi `--muted`.
- Terpilih: latar `--grad-brand-soft`, border gradient berputar (`.glow-border` aktif otomatis lewat `:has(input:checked)`), indikator radio terisi `--brand-600`.
- Keyboard: panah kiri/kanan berpindah pilihan (perilaku radio native), fokus terlihat.

### 6.4 Kartu statistik (`<x-stat-card>`)

- Label `--fs-sm` `--muted`, angka `--fs-2xl` 800 tabular, opsional delta kecil ("+3 dari kemarin").
- Satu kartu paling penting (misalnya "Dalam antrian") boleh memakai `.stat--brand` (latar `--grad-brand-deep`, teks putih). Sisanya putih dengan `.spotlight .lift`.
- Angka memakai `data-count-to` agar menghitung naik saat halaman dibuka dan saat data di-refresh.
- Grid: `repeat(auto-fit, minmax(140px, 1fr))` sehingga 2 kolom di HP dan 4–5 kolom di desktop.

### 6.5 Kartu/baris tiket (`<x-ticket-row>`)

- Kolom: posisi antrian (besar, `--brand-700`), nomor tiket (bold, nowrap), kategori · layanan/modul (`--muted`), waktu relatif, badge status.
- Baris yang baru muncul dari polling diberi kelas `.flash-new` sekali.
- Hover (desktop): latar `--brand-50`. Seluruh baris bisa diklik di admin (link ke detail), dengan fokus keyboard pada link di dalamnya.

### 6.6 Badge (`<x-status-badge>`, `<x-priority-badge>`)

- `--fs-xs`, weight 700, padding 4px 10px, radius pill, warna sesuai tabel bagian 2.
- Badge "Diproses" boleh diberi `.live-dot` kecil di depannya untuk menunjukkan sedang dikerjakan.

### 6.7 Timeline (`<x-timeline>`)

- Garis vertikal 2px `--line` di kiri, titik 10px per item. Titik status memakai warna status; komentar agent = `--brand-500`; komentar pemohon = `--ink`; komentar internal = latar `--st-menunggu-bg` dengan label "Internal"; email = ikon amplop `--muted`.
- Item terbaru di bawah (kronologis). Waktu ditampilkan relatif dengan `title` berisi tanggal lengkap.

### 6.8 Toast, dialog, empty state, skeleton

- Toast: pojok kanan bawah (desktop) / bawah tengah (HP), muncul dengan slide + fade 200ms, hilang otomatis 4 detik untuk sukses, error tetap sampai ditutup. `role="status"` untuk sukses, `role="alert"` untuk error.
- Dialog konfirmasi: pakai elemen native `<dialog>` + `showModal()`; jangan membuat modal dari div.
- Empty state: ikon SVG sederhana, satu kalimat yang mengarahkan tindakan, satu tombol bila relevan.
- Skeleton (`.skeleton`) dipakai saat data dimuat ulang lebih dari 300ms; bentuknya meniru konten asli.

---

## 7. Animasi dan gradient

### 7.1 Katalog efek yang tersedia (`tokens.css` + `motion.js`)

| Kelas | Efek | Dipakai di | Durasi |
| --- | --- | --- | --- |
| `.aurora` / `.aurora--dark` | gradient oranye bergerak sangat pelan | hero form publik, header dashboard, halaman login | 22s loop, alternate |
| `.glow-border` | border conic-gradient berputar | kartu kategori terpilih, kartu tiket yang sedang dibuka | 4s loop, hanya saat aktif |
| `.lift` | kartu naik 3px + bayangan saat hover | kartu kategori, kartu statistik, kartu tiket | 320ms |
| `.spotlight` | cahaya oranye mengikuti kursor | kartu statistik, kartu fitur di landing | mengikuti pointer |
| `.shine` | kilau menyapu tombol saat hover | tombol primary | 700ms sekali per hover |
| `.live-dot` | titik berdenyut | papan antrian, status "Diproses" | 1.8s loop |
| `.flash-new` | latar oranye memudar | baris/kartu baru dari polling | 2.4s sekali |
| `.skeleton` | shimmer | memuat data | 1.4s loop |
| `.rise` + `--i` | naik + fade, bertahap | elemen utama saat halaman pertama dibuka | 600ms + 70ms per item |
| `.reveal-in` | muncul dari atas sedikit | field bersyarat, pesan error, panel yang dibuka | 200ms |
| `data-count-to` | angka menghitung naik | kartu statistik, posisi antrian di halaman sukses | 700ms |

### 7.2 Aturan animasi

1. Hanya animasikan `transform`, `opacity`, `filter` ringan, dan custom property (`--angle`, `--mx`). Jangan animasikan `width`, `height`, `top`, `left`, `margin` (penyebab layout jank). Pengecualian: posisi pseudo-element dekoratif seperti `.shine`.
2. Easing default `--ease-out`. Durasi interaksi 120–320ms; entrance maksimal 600ms.
3. Anggaran per layar: maksimal **satu** kelompok animasi loop dekoratif (aurora, ATAU `.orbs` di satu panel, ATAU glow-border yang aktif) + `.live-dot`. Loop dekoratif hanya boleh di hero form publik, panel seni login, header dashboard, dan halaman sukses/error. Halaman tabel dan detail admin: hanya entrance dan hover.
4. `.rise` hanya saat halaman pertama dibuka, maksimal 6 elemen, tidak diulang saat polling.
5. Animasi loop harus berhenti saat tab tidak aktif atau elemen tidak terlihat bila memakai JavaScript (`document.visibilityState`, `IntersectionObserver`). Animasi CSS loop cukup dibatasi jumlahnya.
6. `prefers-reduced-motion: reduce` wajib mematikan semua loop, entrance, count-up, dan spotlight (sudah diatur di `tokens.css` dan `motion.js`; jangan dilawan).
7. Gradient bergerak tidak boleh berada di belakang teks panjang; letakkan di area judul atau tepi.
8. Tidak boleh ada animasi yang berkedip lebih dari 3 kali per detik.

### 7.3 Contoh pemakaian

```blade
{{-- Kartu kategori --}}
<label class="cat glow-border lift">
    <input type="radio" name="category_id" value="{{ $category->id }}" data-code="{{ $category->code }}">
    <span class="cat-body">
        <span class="cat-name">{{ $category->name }} <span class="tag tag-infra">Perangkat</span></span>
        <span class="cat-desc">{{ $category->description }}</span>
    </span>
</label>

{{-- Kartu statistik --}}
<div class="stat spotlight lift rise" style="--i: 1">
    <span class="stat-label">Diproses</span>
    <span class="stat-value" data-count-to="{{ $stats['diproses'] }}">0</span>
</div>
```

Catatan CSP: atribut `style="--i: 1"` adalah inline style. Karena CSP melarang inline style, gunakan kelas `.i-1` sampai `.i-6` yang didefinisikan di CSS (`.i-1 { --i: 1; }`), atau atur `--i` lewat JavaScript. File demo boleh memakai inline style karena bukan halaman produksi.

### 7.4 Tambahan desain v2

Token baru (di akhir `:root`, token lama tidak berubah): `--r-xl`, `--sh-3`, `--sh-glow`, `--ease-spring`, `--glass`, `--glass-dark`, `--ink-3`, `--grad-ink` (sidebar dan panel gelap), `--grad-text` (judul `.text-gradient`, hanya teks besar ≥ `--fs-xl`).

| Kelas / atribut | Efek | Dipakai di |
| --- | --- | --- |
| `.orbs` + `.orb--1..3`, `.grid-dots` | bola cahaya oranye melayang + pola titik | hero publik, panel login, header dashboard, halaman sukses/error |
| `[data-tilt]` | kartu miring 3D mengikuti pointer (`--rx`/`--ry`) | kartu kategori |
| `.btn` (otomatis) | riak `.ripple` saat klik | semua tombol |
| `.toast` + `[data-toast-close]` | toast dengan tombol tutup + bar progres 5 detik; `--sticky` untuk error | `<x-flash/>` |
| `<x-field reveal>` | tombol lihat/sembunyikan password | login, reset, profil, user |
| `<x-field strength>` | `<meter>` kekuatan password | reset, profil |
| `[data-confetti]`, `.draw-check` | konfeti sekali + centang tergambar | halaman sukses |
| `[data-form-progress]` | bar "x dari n terisi" | form tiket |
| `.stepper--0..3` | langkah status Baru → Diproses → Selesai → Ditutup | tracking |
| `.stub` | kartu nomor tiket berbentuk karcis | halaman sukses |
| `<x-icon name="…">` | ikon SVG garis 24px, `aria-hidden` | tombol, judul panel, meta |

Layout admin: `.admin-content` memakai `grid-template-columns: minmax(0, 1fr)` agar tabel lebar tidak mendorong halaman keluar layar. `.split` (tabel + form) baru dua kolom mulai 1360px; di bawahnya form turun ke bawah tabel.

---

## 8. Spesifikasi visual per halaman

### Form tiket (`/`)
- ≥ 1024px: kiri = panel `.aurora` berisi brand kecil, judul besar, satu kalimat, dan 3 langkah bernomor (Isi form → Masuk antrian → Pantau lewat email) + tautan "Lihat antrian" dengan `.live-dot` dan jumlah tiket aktif. Kanan = panel form putih `--r-lg`.
- < 1024px: judul singkat di atas, langkah disembunyikan di bawah form, form langsung terlihat tanpa scroll jauh.
- Momen wow: kartu kategori dengan `.glow-border`.
- Tombol "Kirim tiket" `primary` + `.shine`, full width di xs.

### Halaman sukses
- Kartu tengah maksimal 640px. Nomor tiket besar (`--fs-2xl`, 800, `--brand-700`) dengan tombol "Salin nomor".
- Garis dekoratif `--grad-brand` 6px di atas kartu. Posisi antrian memakai count-up.
- Dua tombol: "Lihat antrian" (primary) dan "Buat tiket lain" (secondary).

### Papan antrian (`/antrian`)
- Header dengan `.live-dot` + "Diperbarui otomatis" + waktu refresh terakhir.
- 4 kartu statistik kecil, lalu daftar tiket. Tiket yang cocok dengan pencarian: latar `--brand-50` + border kiri 3px `--brand-500`.
- Baris baru dari polling: `.flash-new`. Saat refresh gagal: toast kecil "Gagal memperbarui, mencoba lagi…" tanpa mengosongkan data lama.

### Tracking (`/lacak/...`)
- Kartu ringkasan (nomor, status besar, posisi antrian) → timeline → form balasan.
- Status besar memakai badge ukuran besar; "Diproses" dengan `.live-dot`.

### Login admin
- Layar terbagi: kiri `.aurora--dark` dengan nama aplikasi (disembunyikan di < 768px), kanan form login sederhana.

### Dashboard admin
- Header tipis `.aurora` (tinggi ±120px) berisi sapaan + tanggal.
- Kartu statistik (satu `.stat--brand`), grafik batang SVG (batang `--brand-500`, hover `--brand-700`; ITApps/ITInfra memakai `--apps`/`--infra` dengan legenda teks), tabel antrian.

### Daftar dan detail tiket admin
- Tanpa efek loop. Fokus pada kepadatan informasi dan kecepatan.
- Filter di bar atas (collapsible di HP). Tabel dengan header lengket (`position: sticky`).
- Detail: 2 kolom ≥ 1024px (info + timeline | panel aksi lengket), 1 kolom di bawahnya dengan panel aksi di atas timeline.

---

## 9. Aksesibilitas (wajib)

- Semua input punya `<label for>`; grup radio memakai `<fieldset>` + `<legend>`.
- Urutan tab mengikuti urutan visual. Fokus selalu terlihat (`--focus-ring`); jangan pernah `outline: none` tanpa pengganti.
- Link "Lewati ke konten" di awal halaman admin.
- Konten yang diperbarui otomatis (papan antrian, dashboard) punya wilayah `aria-live="polite"` yang mengumumkan ringkasan singkat ("2 tiket baru"), bukan seluruh tabel.
- Ikon dekoratif `aria-hidden="true"`; tombol ikon punya `aria-label`.
- Bahasa halaman `lang="id"`. Zoom 200% tetap bisa dipakai.
- Semua fitur bisa dijalankan tanpa mouse.

---

## 10. Aturan kode frontend

### Struktur file

```text
public/css/tokens.css      token + utilitas animasi (JANGAN diubah tanpa alasan kuat)
public/css/base.css        reset, tipografi dasar, layout umum, komponen bersama
public/css/public.css      khusus halaman publik
public/css/admin.css       khusus panel admin
public/js/motion.js        efek gerak bersama
public/js/ticket-form.js   form tiket
public/js/queue-board.js   polling papan antrian
public/js/admin.js         drawer, dialog, toast, filter, refresh dashboard
```

Urutan pemuatan CSS: `tokens.css` → `base.css` → `public.css`/`admin.css`. Tambahkan `?v={{ config('app.asset_version') }}` untuk cache busting.

### CSS
- Hanya variabel dari `tokens.css` untuk warna, spasi, radius, bayangan, durasi.
- Hindari selector lebih dari 3 tingkat dan `!important` (kecuali di blok reduced-motion).
- Jangan menulis CSS untuk halaman di dalam file komponen lain; satu komponen, satu blok CSS berlabel komentar.

### JavaScript
- Vanilla ES2020, dibungkus IIFE, `'use strict'`, dimuat dengan `defer`. Tanpa jQuery dan tanpa library.
- Progressive enhancement: form tetap bisa dikirim tanpa JavaScript; JavaScript hanya menambah kenyamanan.
- Seleksi elemen lewat atribut `data-*` (`data-branch`, `data-count-to`), bukan kelas styling.
- Tidak ada `innerHTML` dengan data dari server atau user; pakai `textContent` atau `<template>` + `cloneNode`.
- `fetch` ke endpoint JSON sendiri dengan header `Accept: application/json` dan `X-CSRF-TOKEN` dari meta tag untuk request non-GET.
- Polling berhenti saat `document.hidden` dan lanjut saat tab aktif lagi; ada backoff saat gagal (30s → 60s → 120s).

### Blade
- Tidak ada query atau logika bisnis di view. Data sudah siap dari controller.
- Selalu `{{ }}`. `{!! !!}` hanya untuk `nl2br(e($text))`.
- Tidak ada `<style>` atau `<script>` inline dan tidak ada atribut `style=""`, `onclick=""` (dilarang CSP).
- Teks UI dalam bahasa Indonesia, sentence case, kata kerja aktif pada tombol.

### Performa
- Total CSS < 60 KB, JavaScript < 30 KB per halaman (belum dikompres).
- Font: preconnect ke Google Fonts, hanya 4 weight.
- Largest Contentful Paint < 2.5 detik di 4G; tidak ada layout shift saat font atau data dimuat (siapkan tinggi skeleton).

---

## 11. Dilarang

- Warna selain token, termasuk ungu/biru gradient ala template AI.
- Teks putih di atas `--brand-500` atau `--grad-brand`.
- Emoji sebagai ikon, font ikon, gambar stok.
- Label ALL CAPS dengan huruf renggang di atas setiap judul.
- Semua konten dipotong jadi kartu identik dengan bayangan sama.
- Animasi di setiap elemen, parallax, scroll-jacking, kursor custom.
- Library UI/animasi (Bootstrap, Tailwind CDN, jQuery, GSAP, AOS, Swiper, Chart.js). Grafik digambar dengan SVG dari server.
- Placeholder sebagai pengganti label.
- `alert()`, `confirm()` bawaan browser (pakai `<dialog>`).
- Lorem ipsum atau teks contoh bahasa Inggris di halaman jadi.

---

## 12. Checklist sebelum menyatakan UI selesai

- [ ] Dicek di 360, 390, 768, 1024, 1280, 1440px tanpa scroll horizontal halaman.
- [ ] Semua warna dari token; tidak ada hex baru di luar `tokens.css`.
- [ ] Kontras teks lolos AA (cek teks putih dan teks oranye).
- [ ] Bisa dioperasikan penuh dengan keyboard; fokus selalu terlihat.
- [ ] Dengan `prefers-reduced-motion: reduce`, tidak ada animasi loop atau entrance.
- [ ] Maksimal satu efek loop dekoratif per layar.
- [ ] Tidak ada inline style/script; console browser tanpa error CSP.
- [ ] Form tetap bisa dikirim dengan JavaScript dimatikan.
- [ ] Empty state, loading, dan error sudah didesain, bukan hanya kondisi ideal.
- [ ] Teks UI bahasa Indonesia, sentence case, tombol berupa kata kerja.
