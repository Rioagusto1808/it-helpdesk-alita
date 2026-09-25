{{-- Ikon garis 24px, warna mengikuti currentColor. Selalu dekoratif (aria-hidden); beri teks/aria-label di elemen induk. --}}
@props(['name'])
<svg {{ $attributes->class('icon') }} viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    @switch($name)
        @case('grid')
            <rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>
            @break
        @case('ticket')
            <path d="M4 8a2 2 0 0 0 2-2h12a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H6a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v12" stroke-dasharray="2 2"/>
            @break
        @case('server')
            <rect x="4" y="4" width="16" height="7" rx="2"/><rect x="4" y="13" width="16" height="7" rx="2"/><path d="M8 7.5h.01M8 16.5h.01"/>
            @break
        @case('puzzle')
            <path d="M10 4a2 2 0 0 1 4 0v2h4v4h-2a2 2 0 0 0 0 4h2v4H6v-4h2a2 2 0 0 0 0-4H6V6h4z"/>
            @break
        @case('users')
            <circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3 6"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
            @break
        @case('list')
            <path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('check')
            <path d="M5 12.5l4.5 4.5L19 7.5"/>
            @break
        @case('x')
            <path d="M6 6l12 12M18 6L6 18"/>
            @break
        @case('alert')
            <path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/>
            @break
        @case('info')
            <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>
            @break
        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6"/>
            @break
        @case('arrow-left')
            <path d="M19 12H5M11 6l-6 6 6 6"/>
            @break
        @case('upload')
            <path d="M12 16V4m0 0-4.5 4.5M12 4l4.5 4.5M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
            @break
        @case('file')
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            @break
        @case('search')
            <circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>
            @break
        @case('lock')
            <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>
            @break
        @case('logout')
            <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l-4-4 4-4M6 12h10"/>
            @break
        @case('key')
            <circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3"/>
            @break
        @case('eye')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
            @break
        @case('eye-off')
            <path d="M3 3l18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3 3.9M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
            @break
        @case('copy')
            <rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>
            @break
        @case('laptop')
            <rect x="4" y="5" width="16" height="11" rx="2"/><path d="M2 19h20"/>
            @break
        @case('apps')
            <rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><path d="M17 13v8M13 17h8"/>
            @break
        @case('bolt')
            <path d="M13 2L4 14h7l-1 8 9-12h-7z"/>
            @break
        @case('shield')
            <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>
            @break
        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
            @break
        @case('trash')
            <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>
            @break
        @case('send')
            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/>
            @break
        @case('refresh')
            <path d="M20 11a8 8 0 0 0-14.9-3M4 5v4h4M4 13a8 8 0 0 0 14.9 3M20 19v-4h-4"/>
            @break
        @case('filter')
            <path d="M4 5h16l-6 8v6l-4-2v-4z"/>
            @break
        @case('download')
            <path d="M12 4v12m0 0-4.5-4.5M12 16l4.5-4.5M4 20h16"/>
            @break
        @case('edit')
            <path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 6l4 4"/>
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break
        @case('hand')
            <path d="M8 13V5.5a1.5 1.5 0 0 1 3 0V12M11 11V4.5a1.5 1.5 0 0 1 3 0V12M14 11.5v-5a1.5 1.5 0 0 1 3 0V15a6 6 0 0 1-6 6h-1a6 6 0 0 1-5-2.7l-2.4-3.8a1.5 1.5 0 0 1 2.4-1.8L8 15"/>
            @break
        @case('link-off')
            <path d="M10 14a3.5 3.5 0 0 0 5 0l3-3a3.5 3.5 0 0 0-5-5l-.5.5M14 10a3.5 3.5 0 0 0-5 0l-3 3a3.5 3.5 0 0 0 5 5l.5-.5"/><path d="M4 4l16 16"/>
            @break
        @case('inbox')
            <path d="M4 13l2.5-7h11L20 13v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-5z"/><path d="M4 13h4.5l1 2h5l1-2H20"/>
            @break
        @case('sparkle')
            <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z"/>
            @break
    @endswitch
</svg>
