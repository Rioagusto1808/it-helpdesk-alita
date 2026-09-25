@php($v = config('app.asset_version'))
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
    {{-- Tanpa defer: menambah class "js" ke <html> sebelum render agar drawer tidak berkedip. --}}
    <script src="{{ asset('js/admin.js') }}?v={{ $v }}"></script>
    <script src="{{ asset('js/motion.js') }}?v={{ $v }}" defer></script>
    @stack('scripts')
</head>
<body>
    <a class="skip-link" href="#konten">Lewati ke konten</a>

    <div class="admin-shell">
        <aside class="sidebar" id="sidebar" data-drawer aria-label="Menu admin">
            <a class="brand sidebar-brand" href="{{ route('admin.dashboard') }}">
                <svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="2" y="2" width="20" height="20" rx="6" fill="currentColor"/>
                    <path class="brand-mark-check" d="M8 12.5l2.5 2.5L16 9.5"/>
                </svg>
                <span>Helpdesk <strong>Admin</strong></span>
            </a>
            <nav class="side-nav" aria-label="Navigasi admin">
                @foreach ($menu as $item)
                    <a class="side-link" href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            <a class="side-public" href="{{ route('queue.index') }}">Lihat antrian publik</a>
        </aside>
        <div class="drawer-backdrop" data-drawer-close hidden></div>

        <div class="admin-main">
            <header class="topbar">
                <button type="button" class="btn btn--ghost topbar-menu" data-drawer-toggle aria-controls="sidebar" aria-expanded="false">
                    <x-icon name="menu" /> Menu
                </button>
                <div class="topbar-user">
                    <span class="topbar-name">
                        {{ auth()->user()->name }}
                        <span class="topbar-role">{{ auth()->user()->role->label() }}</span>
                    </span>
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.profile.password') }}">Ganti password</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn--secondary btn--sm">Keluar</button>
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
