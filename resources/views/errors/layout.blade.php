{{-- Layout halaman error: mandiri, tanpa session/auth/query agar tetap tampil saat database atau session bermasalah. --}}
@php($v = config('app.asset_version'))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('helpdesk.app_name') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/public.css') }}?v={{ $v }}">
    <script src="{{ asset('js/motion.js') }}?v={{ $v }}"></script>
</head>
<body>
    <main class="error-page" id="konten">
        <div class="orbs" aria-hidden="true"><span class="orb orb--1"></span><span class="orb orb--2"></span><span class="orb orb--3"></span></div>
        <div class="grid-dots" aria-hidden="true"></div>

        <section class="card-narrow card card--accent rise" aria-labelledby="error-title">
            <span class="error-code text-gradient" aria-hidden="true">@yield('code')</span>
            <div>
                <h1 id="error-title" class="page-title">@yield('title')</h1>
                <p class="page-lede">@yield('message')</p>
            </div>
            @hasSection('action')
                @yield('action')
            @else
                <a class="btn btn--primary btn--block shine" href="{{ url('/') }}"><x-icon name="arrow-left" /> Kembali ke beranda</a>
            @endif
        </section>
    </main>
</body>
</html>
