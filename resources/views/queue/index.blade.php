@extends('layouts.public')

@section('title', 'Antrian tiket')

@push('scripts')
    <script src="{{ asset('js/queue-board.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
@php
    $stats = [
        'baru' => ['Baru', 'inbox', 'baru'],
        'diproses' => ['Diproses', 'bolt', 'diproses'],
        'menunggu' => ['Menunggu', 'clock', 'menunggu'],
        'selesai_hari_ini' => ['Selesai hari ini', 'check', 'selesai'],
    ];
    $filters = [null => 'Semua', \App\Models\Category::ITAPPS => 'ITApps', \App\Models\Category::ITINFRA => 'ITInfra'];
@endphp
<div class="queue-page" data-queue-board data-url="{{ route('queue.data', array_filter(['kategori' => $category])) }}">
    <header class="page-hero rise">
        <div class="orbs" aria-hidden="true"><span class="orb orb--1"></span><span class="orb orb--2"></span></div>
        <div class="grid-dots" aria-hidden="true"></div>
        <div>
            <p class="kicker"><span class="kicker-icon"><x-icon name="list" /></span> Transparan untuk semua</p>
            <h1 class="page-title">Antrian tiket <span class="text-gradient">IT</span></h1>
            <p class="queue-live">
                <span class="live-dot" aria-hidden="true"></span>
                Diperbarui otomatis, terakhir <time data-updated-at>{{ now()->format('H.i') }}</time>
            </p>
        </div>
        <a class="btn btn--primary shine" href="{{ route('tickets.create') }}"><x-icon name="plus" /> Buat tiket</a>
    </header>

    <div class="stats">
        @foreach ($stats as $key => [$label, $icon, $variant])
            <x-stat-card :label="$label" :value="$board['summary'][$key]" :field="$key" :icon="$icon" :variant="$variant" class="rise i-{{ $loop->iteration }}" />
        @endforeach
    </div>

    <div class="queue-tools">
        <nav class="chips" aria-label="Filter kategori">
            @foreach ($filters as $code => $label)
                <a class="chip" href="{{ route('queue.index', array_filter(['kategori' => $code])) }}" @if ($category === ($code ?: null)) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <form class="queue-search" method="GET" action="{{ route('queue.index') }}" role="search">
            @if ($category)
                <input type="hidden" name="kategori" value="{{ $category }}">
            @endif
            <label class="sr-only" for="cari">Cari nomor tiket</label>
            <span class="input-wrap has-icon">
                <x-icon name="search" class="input-icon" />
                <input class="input" type="search" id="cari" name="cari" value="{{ $search }}" placeholder="Cari nomor tiket" autocomplete="off" data-queue-search>
            </span>
            <button class="btn btn--secondary" type="submit">Cari</button>
        </form>
    </div>

    <div class="queue-card card reveal">
        <table class="queue-table">
            <caption class="sr-only">Tiket aktif, urut sesuai posisi antrian</caption>
            <thead>
                <tr>
                    <th scope="col">Posisi</th>
                    <th scope="col">Tiket</th>
                    <th scope="col" class="qrow-time">Masuk</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody data-queue-rows>
                @foreach ($board['rows'] as $row)
                    @include('queue._row', ['row' => $row])
                @endforeach
            </tbody>
        </table>

        <x-empty-state title="Belum ada tiket aktif. Semua kendala sudah tertangani." icon="check" data-queue-empty :hidden="$board['rows'] !== []">
            <a class="btn btn--secondary btn--sm" href="{{ route('tickets.create') }}">Buat tiket</a>
        </x-empty-state>

        <p class="queue-more" data-queue-more @if ($board['more'] === 0) hidden @endif>
            dan <span data-more-count>{{ $board['more'] }}</span> tiket lainnya
        </p>
    </div>

    <p class="queue-foot"><x-icon name="search" class="icon--sm" /> Punya tiket di antrian? <a href="{{ route('tracking.lookup') }}">Cari tiket kamu</a> untuk melihat detail dan membalas.</p>

    <template data-row-template>@include('queue._row', ['row' => null])</template>
    <p class="sr-only" aria-live="polite" data-queue-announce></p>
    <div class="toast-stack">
        <div class="toast toast--error toast--sticky" role="alert" data-queue-error hidden>
            <span class="toast-icon"><x-icon name="refresh" /></span>
            <p>Gagal memperbarui, mencoba lagi…</p>
            <span></span>
        </div>
    </div>
</div>
@endsection
