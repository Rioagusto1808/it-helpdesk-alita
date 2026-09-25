@php($v = config('app.asset_version'))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('helpdesk.app_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ $v }}">
    <script src="{{ asset('js/motion.js') }}?v={{ $v }}"></script>
</head>
<body class="auth">
    <div class="auth-shell">
        <aside class="auth-art" aria-hidden="true">
            <div class="orbs"><span class="orb orb--1"></span><span class="orb orb--2"></span><span class="orb orb--3"></span></div>
            <div class="grid-dots grid-dots--light"></div>

            <span class="brand brand--light">
                <span class="brand-mark"><x-icon name="check" /></span>
                <span>IT Helpdesk <strong>Alita</strong></span>
            </span>

            <div class="auth-art-body">
                <p class="auth-art-title">Pusat kendali <span>tim IT Alita.</span></p>
                <p class="auth-art-text">Kerjakan tiket, pantau antrian, dan pastikan setiap laporan karyawan terjawab tepat waktu.</p>
                <ul class="auth-features">
                    <li><span class="auth-feature-icon"><x-icon name="bolt" /></span> Antrian dan dashboard real-time</li>
                    <li><span class="auth-feature-icon"><x-icon name="mail" /></span> Email otomatis ke pemohon dan tim</li>
                    <li><span class="auth-feature-icon"><x-icon name="shield" /></span> Audit log untuk setiap aksi</li>
                </ul>
            </div>

            <p class="auth-art-foot">Khusus tim IT. Pelapor tidak perlu login.</p>
        </aside>

        <main class="auth-panel" id="konten">
            <div class="auth-card">
                <a class="brand auth-mobile-brand" href="{{ route('tickets.create') }}" aria-label="IT Helpdesk Alita, halaman buat tiket">
                    <span class="brand-mark"><x-icon name="check" /></span>
                    <span>IT Helpdesk <strong>Alita</strong></span>
                </a>
                @yield('content')
            </div>
        </main>
    </div>

    <x-flash />
</body>
</html>
