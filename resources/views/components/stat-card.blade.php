{{-- $field: nama field di JSON /admin/dashboard/data agar angkanya ikut diperbarui. --}}
@props(['label', 'value', 'field' => null, 'hint' => null, 'brand' => false])
<div {{ $attributes->class(['stat', 'stat--brand' => $brand, 'spotlight lift' => ! $brand]) }}>
    <span class="stat-label">{{ $label }}</span>
    <span class="stat-value" @if ($field) data-stat="{{ $field }}" @endif data-count-to="{{ $value }}">{{ $value }}</span>
    @if ($hint)
        <span class="stat-hint">{{ $hint }}</span>
    @endif
</div>
