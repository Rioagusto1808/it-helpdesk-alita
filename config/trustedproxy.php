<?php

declare(strict_types=1);

// Production berjalan di belakang WAF/reverse proxy (Sophos). Dibaca middleware TrustProxies bawaan Laravel
// agar URL tetap https:// dan IP pemohon (rate limit, audit log) adalah IP asli, bukan IP proxy.
// Isi IP proxy dipisah koma, atau * jika server origin hanya bisa dijangkau lewat proxy. Kosong = tidak ada proxy.
return [
    'proxies' => env('TRUSTED_PROXIES'),
];
