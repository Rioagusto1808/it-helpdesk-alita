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
</head>
<body>
    <div class="auth-shell">
        <aside class="auth-art aurora aurora--dark" aria-hidden="true">
            <div class="auth-art-inner">
                <p class="auth-art-title">IT Helpdesk Alita</p>
                <p class="auth-art-text">Panel tim IT untuk mengerjakan tiket, memantau antrian, dan menjaga setiap laporan terjawab.</p>
            </div>
        </aside>

        <main class="auth-panel" id="konten">
            <div class="auth-card rise">
                <a class="brand" href="{{ route('tickets.create') }}" aria-label="IT Helpdesk Alita, halaman buat tiket">
                    <svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="2" y="2" width="20" height="20" rx="6" fill="currentColor"/>
                        <path class="brand-mark-check" d="M8 12.5l2.5 2.5L16 9.5"/>
                    </svg>
                    <span>IT Helpdesk <strong>Alita</strong></span>
                </a>
                @yield('content')
            </div>
        </main>
    </div>

    <x-flash />
</body>
</html>
