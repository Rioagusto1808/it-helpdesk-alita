{{-- $live=false di tabel/detail admin: titik statis, tanpa denyut (tidak ada efek loop di area kerja admin). --}}
@props(['status', 'live' => true])
@php($pulse = $live && $status === \App\Enums\TicketStatus::Diproses)
<span {{ $attributes->class(['badge', $status->badgeClass(), 'badge--dot' => ! $pulse]) }}>
    @if ($pulse)
        <span class="live-dot" aria-hidden="true"></span>
    @endif
    {{ $status->label() }}
</span>
