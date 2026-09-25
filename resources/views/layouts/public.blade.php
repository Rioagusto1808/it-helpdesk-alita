@php($v = config('app.asset_version'))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ config('helpdesk.app_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/public.css') }}?v={{ $v }}">
    {{-- Tanpa defer: memasang class "js" sebelum render agar animasi masuk tidak berkedip. --}}
    <script src="{{ asset('js/motion.js') }}?v={{ $v }}"></script>
    @stack('scripts')
</head>
<body class="public">
    <a class="skip-link" href="#konten">Lewati ke konten</a>
    <div class="page-decor" aria-hidden="true"><span class="orb orb--1"></span><span class="orb orb--3"></span></div>

    <header class="site-header" data-sticky-header>
        <div class="container site-header-inner">
            <a class="brand" href="{{ route('tickets.create') }}" aria-label="IT Helpdesk Alita, buat tiket">
                <span class="brand-mark"><x-icon name="check" /></span>
                <span><span class="brand-long">IT Helpdesk </span><strong>Alita</strong></span>
            </a>
            <nav class="site-nav" aria-label="Navigasi utama">
                <a href="{{ route('tickets.create') }}" @if (request()->routeIs('tickets.*')) aria-current="page" @endif>
                    <x-icon name="plus" class="icon--sm nav-icon" /> Buat tiket
                </a>
                <a href="{{ route('queue.index') }}" @if (request()->routeIs('queue.*')) aria-current="page" @endif>
                    <x-icon name="list" class="icon--sm nav-icon" /> Lihat antrian
                </a>
            </nav>
        </div>
    </header>

    <main id="konten" class="container site-main">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container site-footer-inner">
            <p class="site-footer-brand"><strong>IT Helpdesk Alita</strong> · dikelola tim IT</p>
            <nav class="site-footer-nav" aria-label="Tautan bawah">
                <a href="{{ route('tickets.create') }}">Buat tiket</a>
                <a href="{{ route('queue.index') }}">Lihat antrian</a>
                <a href="{{ route('tracking.lookup') }}">Cari tiket</a>
                <a href="{{ route('admin.login') }}">Masuk tim IT</a>
            </nav>
        </div>
    </footer>

    <x-flash />
</body>
</html>
