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
    <script src="{{ asset('js/motion.js') }}?v={{ $v }}" defer></script>
    @stack('scripts')
</head>
<body>
    <a class="skip-link" href="#konten">Lewati ke konten</a>

    <header class="site-header">
        <div class="container site-header-inner">
            <a class="brand" href="{{ route('tickets.create') }}" aria-label="IT Helpdesk Alita, buat tiket">
                <svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="2" y="2" width="20" height="20" rx="6" fill="currentColor"/>
                    <path class="brand-mark-check" d="M8 12.5l2.5 2.5L16 9.5"/>
                </svg>
                <span><span class="brand-long">IT Helpdesk </span><strong>Alita</strong></span>
            </a>
            <nav class="site-nav" aria-label="Navigasi utama">
                <a href="{{ route('tickets.create') }}" @if (request()->routeIs('tickets.*')) aria-current="page" @endif>Buat tiket</a>
                <a href="{{ route('queue.index') }}" @if (request()->routeIs('queue.*')) aria-current="page" @endif>Lihat antrian</a>
            </nav>
        </div>
    </header>

    <main id="konten" class="container">
        @yield('content')
    </main>

    <x-flash />
</body>
</html>
