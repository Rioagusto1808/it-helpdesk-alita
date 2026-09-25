@php($v = config('app.asset_version'))
@php($me = auth()->user())
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Admin {{ config('helpdesk.app_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ $v }}">
    {{-- Tanpa defer: menambah class "js" ke <html> sebelum render agar drawer & animasi tidak berkedip. --}}
    <script src="{{ asset('js/motion.js') }}?v={{ $v }}"></script>
    <script src="{{ asset('js/admin.js') }}?v={{ $v }}"></script>
    @stack('scripts')
</head>
<body class="admin">
    <a class="skip-link" href="#konten">Lewati ke konten</a>

    <div class="admin-shell">
        <aside class="sidebar" id="sidebar" data-drawer aria-label="Menu admin">
            <div class="sidebar-glow" aria-hidden="true"></div>
            <a class="brand brand--light sidebar-brand" href="{{ route('admin.dashboard') }}">
                <span class="brand-mark"><x-icon name="check" /></span>
                <span>Helpdesk <strong>Admin</strong></span>
            </a>

            <p class="side-heading">Menu</p>
            <nav class="side-nav" aria-label="Navigasi admin">
                @foreach ($menu as $item)
                    <a class="side-link" href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif>
                        <span class="side-link-icon"><x-icon :name="$item['icon']" /></span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <a class="side-public" href="{{ route('queue.index') }}"><x-icon name="list" class="icon--sm" /> Lihat antrian publik</a>

            <div class="side-user">
                <span class="avatar" aria-hidden="true">{{ $me->initials() }}</span>
                <span class="side-user-text">
                    <strong>{{ $me->name }}</strong>
                    <span>{{ $me->role->label() }}</span>
                </span>
            </div>
        </aside>
        <div class="drawer-backdrop" data-drawer-close hidden></div>

        <div class="admin-main">
            <header class="topbar">
                <button type="button" class="btn btn--ghost topbar-menu" data-drawer-toggle aria-controls="sidebar" aria-expanded="false">
                    <x-icon name="menu" /> Menu
                </button>
                <p class="topbar-title">@yield('title')</p>
                <div class="topbar-user">
                    <span class="topbar-name">
                        {{ $me->name }}
                        <span class="topbar-role">{{ $me->role->label() }}</span>
                    </span>
                    <span class="avatar avatar--sm" aria-hidden="true">{{ $me->initials() }}</span>
                    <a class="btn btn--ghost btn--sm topbar-link" href="{{ route('admin.profile.password') }}"><x-icon name="key" class="icon--sm" /> <span>Ganti password</span></a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn--secondary btn--sm"><x-icon name="logout" class="icon--sm" /> Keluar</button>
                    </form>
                </div>
            </header>

            <main id="konten" class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>

    <x-flash />
</body>
</html>
