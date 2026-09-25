@props(['title', 'icon' => 'inbox'])
<div {{ $attributes->class('empty') }}>
    <span class="empty-art" aria-hidden="true"><x-icon :name="$icon" /></span>
    <p class="empty-title">{{ $title }}</p>
    {{ $slot }}
</div>
