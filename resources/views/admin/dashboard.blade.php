@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $duration = fn (?int $minutes) => $minutes === null ? '-' : \Carbon\CarbonInterval::minutes($minutes)->cascade()->forHumans(['parts' => 2]);
@endphp

<header class="dash-hero aurora rise">
    <h1 class="page-title">Halo, {{ Str::before(auth()->user()->name, ' ') }}</h1>
    <p class="page-lede">{{ now()->translatedFormat('l, d F Y') }}</p>
</header>

<div class="stats" data-dashboard data-url="{{ route('admin.dashboard.data') }}">
    <x-stat-card brand label="Baru" :value="$summary['baru']" field="baru" hint="Belum ditangani" class="rise i-1" />
    <x-stat-card label="Diproses" :value="$summary['diproses']" field="diproses" class="rise i-2" />
    <x-stat-card label="Menunggu" :value="$summary['menunggu']" field="menunggu" class="rise i-3" />
    <x-stat-card label="Selesai hari ini" :value="$summary['selesai_hari_ini']" field="selesai_hari_ini" class="rise i-4" />
    <x-stat-card label="Lewat SLA" :value="$summary['lewat_sla']" field="lewat_sla" hint="Tiket aktif" class="rise i-5" />
</div>
<p class="sr-only" aria-live="polite" data-dashboard-announce></p>

<div class="dash-grid">
    <section class="panel" aria-labelledby="chart-heading">
        <h2 id="chart-heading" class="section-title">Tiket masuk 14 hari terakhir</h2>
        <x-bar-chart :days="$daily" />
    </section>

    <section class="panel dash-side" aria-labelledby="avg-heading">
        <h2 id="avg-heading" class="section-title">Rata-rata 30 hari</h2>
        <dl class="dash-avg">
            <div><dt>Respons pertama</dt><dd>{{ $duration($summary['avg_response_minutes']) }}</dd></div>
            <div><dt>Penyelesaian</dt><dd>{{ $duration($summary['avg_resolve_minutes']) }}</dd></div>
        </dl>

        <h2 class="section-title">Layanan/modul terbanyak</h2>
        @if ($topTypes === [])
            <p class="action-note">Belum ada tiket dalam 30 hari terakhir.</p>
        @else
            <ol class="top-list">
                @foreach ($topTypes as $type)
                    <li>
                        <span><strong>{{ $type['name'] }}</strong> <span class="cell-sub">{{ $type['category'] }}</span></span>
                        <span class="top-list-total">{{ $type['total'] }}</span>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>

<div class="dash-tables">
    <section class="panel" aria-labelledby="queue-heading">
        <div class="panel-head">
            <h2 id="queue-heading" class="section-title">Antrian saat ini</h2>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.tickets.index') }}">Lihat semua</a>
        </div>
        @if ($queue->isEmpty())
            <x-empty-state title="Antrian kosong. Tidak ada tiket baru atau yang sedang diproses." />
        @else
            <table class="ticket-table ticket-table--compact">
                <thead><tr><th scope="col">Posisi</th><th scope="col">Nomor</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Petugas</th></tr></thead>
                <tbody>
                    @foreach ($queue as $ticket)
                        <tr class="trow">
                            <td data-label="Posisi" class="cell-pos">{{ $loop->iteration }}</td>
                            <td class="trow-no">
                                <a class="trow-link" href="{{ route('admin.tickets.show', $ticket) }}">{{ $ticket->ticket_no }}</a>
                                @if ($ticket->isOverdue()) <span class="badge badge--sla">Lewat SLA</span> @endif
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

    <section class="panel" aria-labelledby="mine-heading">
        <div class="panel-head">
            <h2 id="mine-heading" class="section-title">Tiket saya</h2>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.tickets.index', ['petugas' => 'me']) }}">Lihat semua</a>
        </div>
        @if ($mine->isEmpty())
            <x-empty-state title="Belum ada tiket aktif yang kamu tangani." />
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
