---
name: alita-ui
description: Aturan tampilan Alita IT Helpdesk. Pakai setiap kali membuat atau mengubah Blade view, CSS, JavaScript frontend, email HTML, atau komponen UI di proyek ini (form tiket, papan antrian, tracking, dashboard, panel admin).
---

# Alita UI

Sebelum menulis kode tampilan:

1. Baca `docs/FRONTEND_RULES.md` (wajib) dan bagian halaman terkait di `docs/PRD.md`.
2. Pakai `public/css/tokens.css` untuk semua warna, spasi, radius, bayangan, dan animasi. Lihat `docs/snippets/components-demo.html` untuk contoh.
3. Jika taste-skill aktif, pakai dial: publik VARIANCE 6 / MOTION 7 / DENSITY 4; admin 3 / 4 / 7. Abaikan saran React, Tailwind, GSAP, atau library lain.

Ringkasan aturan:

- Brand oranye `#E97537` (`--brand-500`) untuk aksen; tombol/link berteks memakai `--brand-700`. Teks putih tidak boleh di atas `--brand-500` atau `--grad-brand`.
- Netral abu-abu dingin (`--ink`, `--muted`, `--line`, `--canvas`). Plus Jakarta Sans.
- Mobile-first, uji 360–1440px, tanpa scroll horizontal, target sentuh 44px.
- Satu efek loop dekoratif per layar (aurora ATAU glow-border). Admin tabel/detail tanpa efek loop.
- Animasi hanya transform/opacity/custom property; hormati `prefers-reduced-motion`.
- Blade component untuk elemen berulang; `{{ }}` selalu; tanpa inline style/script (CSP); gunakan kelas `.i-1`–`.i-6` untuk stagger.
- JavaScript vanilla, `defer`, progressive enhancement, `textContent` bukan `innerHTML`.
- Kode minimal: elemen native dulu (`<dialog>`, `<details>`, `<input type="date">`), CSS sebelum JavaScript.

Selesai jika checklist `FRONTEND_RULES.md` §12 terpenuhi.
