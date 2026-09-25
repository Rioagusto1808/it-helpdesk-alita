{{-- Ikon garis 24px, warna mengikuti currentColor. --}}
@props(['name'])
<svg {{ $attributes->class('icon') }} viewBox="0 0 24 24" aria-hidden="true">
    @switch($name)
        @case('grid')
            <rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>
            @break
        @case('ticket')
            <path d="M4 8a2 2 0 0 0 2-2h12a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H6a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v12"/>
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
        @case('list')
            <path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
    @endswitch
</svg>
