# Kode Tahap 1 — hanya referensi

Kode di folder ini dibuat sebelum PRD final. Jangan disalin apa adanya ke `app/`, `routes/`, atau `resources/`.
Agent memakainya sebagai contoh lalu menulis ulang sesuai `docs/PRD.md`, `docs/BACKEND_RULES.md`, dan `docs/FRONTEND_RULES.md`.

Yang sudah tidak berlaku:

| Di Tahap 1 | Yang benar sekarang |
| --- | --- |
| Warna biru (ITInfra) / hijau (ITPass) sebagai warna utama | Oranye Alita dari `public/css/tokens.css`; biru/hijau hanya tag kecil |
| MySQL | PostgreSQL |
| Satu migration untuk semua tabel | Satu migration per tabel, kolom sesuai PRD (ada kolom baru: `last_activity_at`, `actor_label`, `attempts`, dll.) |
| URL `/tickets`, `/tickets/terkirim` | `/tiket`, `/tiket/terkirim` |
| Logika simpan tiket di controller | Action `CreateTicket` |
| `helpdesk.css` / `helpdesk.js` | `tokens.css` + `base.css` + `public.css`, `motion.js` + `ticket-form.js` |
| Baris `<script>` inline di layout | Dilarang CSP; pindahkan ke file JS |
| Posisi antrian hanya berdasarkan ID | Prioritas dulu, lalu ID |

Folder ini boleh dihapus setelah M2 selesai.
