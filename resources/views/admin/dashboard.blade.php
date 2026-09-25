@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $duration = fn (?int $minutes) => $minutes === null ? '-' : \Carbon\CarbonInterval::minutes($minutes)->cascade()->forHumans(['parts' => 2]);
    $topMax = max([1, ...array_column($topTypes, 'total')]);
@endphp

<header class="dash-hero rise">
    <div class="orbs" aria-hidden="true"><span class="orb orb--1"></span><span class="orb orb--2"></span></div>
    <div class="grid-dots grid-dots--light" aria-hidden="true"></div>
    <div>
        <h1 class="page-title">Halo, <span>{{ Str::before(auth()->user()->name, ' ') }}</span></h1>
        <p class="page-lede">{{ now()->translatedFormat('l, d F Y') }} · {{ $summary['baru'] }} tiket baru menunggu ditangani.</p>
    </div>
    <div class="dash-hero-actions">
        <a class="btn btn--primary shine" href="{{ route('admin.tickets.index', ['status' => 'baru']) }}"><x-icon name="inbox" /> Tiket baru</a>
        <a class="btn btn--secondary" href="{{ route('admin.tickets.index', ['petugas' => 'me']) }}"><x-icon name="user" /> Tiket saya</a>
    </div>
</header>

<div class="stats" data-dashboard data-url="{{ route('admin.dashboard.data') }}">
    <x-stat-card brand label="Baru" :value="$summary['baru']" field="baru" hint="Belum ditangani" icon="inbox" class="rise i-1" />
    <x-stat-card label="Diproses" :value="$summary['diproses']" field="diproses" icon="bolt" variant="diproses" class="rise i-2" />
    <x-stat-card label="Menunggu" :value="$summary['menunggu']" field="menunggu" icon="clock" variant="menunggu" class="rise i-3" />
    <x-stat-card label="Selesai hari ini" :value="$summary['selesai_hari_ini']" field="selesai_hari_ini" icon="check" variant="selesai" class="rise i-4" />
    <x-stat-card label="Lewat SLA" :value="$summary['lewat_sla']" field="lewat_sla" hint="Tiket aktif" icon="alert" variant="sla" class="rise i-5" />
</div>
<p class="sr-only" aria-live="polite" data-dashboard-announce></p>

<div class="dash-grid">
    <section class="panel reveal" aria-labelledby="chart-heading">
        <div class="panel-head">
            <h2 id="chart-heading" class="panel-title"><span class="panel-title-icon"><x-icon name="chart" /></span> Tiket masuk 14 hari terakhir</h2>
        </div>
        <x-bar-chart :days="$daily" />

        <div class="dash-avg-block">
            <h2 id="avg-heading" class="panel-title"><span class="panel-title-icon"><x-icon name="clock" /></span> Rata-rata 30 hari</h2>
            <dl class="dash-avg">
                <div><dt><x-icon name="bolt" /> Respons pertama</dt><dd>{{ $duration($summary['avg_response_minutes']) }}</dd></div>
                <div><dt><x-icon name="check" /> Penyelesaian</dt><dd>{{ $duration($summary['avg_resolve_minutes']) }}</dd></div>
            </dl>
        </div>
    </section>

    <section class="panel dash-side reveal i-1" aria-labelledby="top-heading">
        <h2 id="top-heading" class="panel-title"><span class="panel-title-icon"><x-icon name="sparkle" /></span> Layanan/modul terbanyak</h2>
        @if ($topTypes === [])
            <p class="action-note">Belum ada tiket dalam 30 hari terakhir.</p>
        @else
            <ol class="top-list">
                @foreach ($topTypes as $type)
                    <li>
                        <span><strong>{{ $type['name'] }}</strong> <span class="cell-sub">{{ $type['category'] }}</span></span>
                        <span class="top-list-total">{{ $type['total'] }}</span>
                        <meter min="0" max="{{ $topMax }}" value="{{ $type['total'] }}" aria-label="{{ $type['name'] }}: {{ $type['total'] }} tiket"></meter>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>

<div class="dash-tables">
    <section class="panel reveal" aria-labelledby="queue-heading">
        <div class="panel-head">
            <h2 id="queue-heading" class="panel-title"><span class="panel-title-icon"><x-icon name="list" /></span> Antrian saat ini</h2>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.tickets.index') }}">Lihat semua <x-icon name="arrow-right" class="btn-arrow" /></a>
        </div>
        @if ($queue->isEmpty())
            <x-empty-state title="Antrian kosong. Tidak ada tiket baru atau yang sedang diproses." icon="check" />
        @else
            <table class="ticket-table ticket-table--compact">
                <thead><tr><th scope="col">Posisi</th><th scope="col">Nomor</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Petugas</th></tr></thead>
                <tbody>
                    @foreach ($queue as $ticket)
                        <tr class="trow">
                            <td data-label="Posisi" class="cell-pos">{{ $loop->iteration }}</td>
                            <td class="trow-no">
                                <a class="trow-link" href="{{ route('admin.tickets.show', $ticket) }}">{{ $ticket->ticket_no }}</a>
                                @if ($ticket->isOverdue()) <span class="badge badge--sla badge--dot">Lewat SLA</span> @endif
                            </td>
                            <td data-label="Jenis">{{ $ticket->category->name }} · {{ $ticket->typeLabel() }}</td>
                            <td data-label="Status"><x-status-badge :status="$ticket->status" :live="false" /></td>
                            <td data-label="Petugas">{{ $ticket->assignee->name ?? 'Belum ada' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="panel reveal i-1" aria-labelledby="mine-heading">
        <div class="panel-head">
            <h2 id="mine-heading" class="panel-title"><span class="panel-title-icon"><x-icon name="user" /></span> Tiket saya</h2>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.tickets.index', ['petugas' => 'me']) }}">Lihat semua <x-icon name="arrow-right" class="btn-arrow" /></a>
        </div>
        @if ($mine->isEmpty())
            <x-empty-state title="Belum ada tiket aktif yang kamu tangani." icon="hand" />
        @else
            <table class="ticket-table ticket-table--compact">
                <thead><tr><th scope="col">Nomor</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Aktivitas</th></tr></thead>
                <tbody>
                    @foreach ($mine as $ticket)
                        <tr class="trow">
                            <td class="trow-no"><a class="trow-link" href="{{ route('admin.tickets.show', $ticket) }}">{{ $ticket->ticket_no }}</a></td>
                            <td data-label="Jenis">{{ $ticket->category->name }} · {{ $ticket->typeLabel() }}</td>
                            <td data-label="Status"><x-status-badge :status="$ticket->status" :live="false" /></td>
                            <td data-label="Aktivitas"><time datetime="{{ $ticket->last_activity_at?->toIso8601String() }}">{{ $ticket->last_activity_at?->diffForHumans(null, true) ?? '-' }}</time></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
@endsection
