{{--
    $field: nama field di JSON (/antrian/data, /admin/dashboard/data) agar angkanya ikut diperbarui.
    $variant: baru|diproses|menunggu|selesai|sla untuk warna ikon; $brand: kartu utama bergradient.
--}}
@props(['label', 'value', 'field' => null, 'hint' => null, 'brand' => false, 'icon' => 'chart', 'variant' => null])
<div {{ $attributes->class(['stat', 'stat--brand' => $brand, 'spotlight lift' => ! $brand, 'stat--'.$variant => $variant]) }}>
    <div class="stat-head">
        <span class="stat-label">{{ $label }}</span>
        <span class="stat-icon" aria-hidden="true"><x-icon :name="$icon" /></span>
    </div>
    <span class="stat-value" @if ($field) data-stat="{{ $field }}" @endif data-count-to="{{ $value }}">{{ $value }}</span>
    @if ($hint)
        <span class="stat-hint">{{ $hint }}</span>
    @endif
</div>
