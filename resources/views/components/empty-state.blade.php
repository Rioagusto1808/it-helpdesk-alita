@props(['title'])
<div {{ $attributes->class('empty') }}>
    <svg class="empty-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 13l2.5-7h11L20 13v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-5z"/>
        <path d="M4 13h4.5l1 2h5l1-2H20"/>
    </svg>
    <p class="empty-title">{{ $title }}</p>
    {{ $slot }}
</div>
