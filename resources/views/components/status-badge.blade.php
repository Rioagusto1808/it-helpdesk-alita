{{-- $live=false di tabel/detail admin: tanpa titik berdenyut (tidak ada efek loop di area kerja admin). --}}
@props(['status', 'live' => true])
<span {{ $attributes->class(['badge', $status->badgeClass()]) }}>
    @if ($live && $status === \App\Enums\TicketStatus::Diproses)
        <span class="live-dot" aria-hidden="true"></span>
    @endif
    {{ $status->label() }}
</span>
