---
name: alita-laravel
description: Aturan backend Alita IT Helpdesk. Pakai setiap kali menulis atau mengubah kode PHP/Laravel di proyek ini: migration, model, enum, Action, controller, Form Request, Policy, job, mail, route, command, atau test.
---

# Alita Laravel

Sebelum menulis kode backend:

1. Baca `docs/BACKEND_RULES.md` (wajib) dan bagian terkait di `docs/PRD.md` (skema, aturan bisnis, routes, email).
2. Cek apakah sudah ada Action, scope, enum, atau komponen yang bisa dipakai ulang.

Ringkasan aturan:

- Alur: Route → Middleware → Controller tipis → Form Request → Policy → Action (transaksi, audit log) → Model; email via `TicketNotifier` + queue `afterCommit`.
- `declare(strict_types=1)`, type hint lengkap, `final` class, early return, method ±20 baris.
- Enum untuk status/prioritas/role; transisi status lewat `TicketStatus::canTransitionTo()`.
- `$fillable` eksplisit, eager loading, paginate, whitelist sort, tidak ada `env()` di luar `config/`.
- Upload di disk `local`, unduh lewat controller berotorisasi. Pemohon hanya lewat signed URL.
- Tanpa paket composer baru, tanpa repository/interface/DTO yang tidak perlu (ponytail), tapi validasi/otorisasi/rate limit/test tidak pernah dipangkas.
- Setiap fitur punya feature test; jalankan test, Pint, Larastan sebelum selesai, lalu `/ponytail-review`.

Selesai jika definition of done `BACKEND_RULES.md` §20 terpenuhi.
