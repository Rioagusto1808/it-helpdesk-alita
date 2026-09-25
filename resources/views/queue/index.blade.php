@extends('layouts.public')

@section('title', 'Antrian tiket')

@push('scripts')
    <script src="{{ asset('js/queue-board.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
@php
    $stats = ['baru' => 'Baru', 'diproses' => 'Diproses', 'menunggu' => 'Menunggu', 'selesai_hari_ini' => 'Selesai hari ini'];
    $filters = [null => 'Semua', \App\Models\Category::ITAPPS => 'ITApps', \App\Models\Category::ITINFRA => 'ITInfra'];
@endphp
<div class="queue-page" data-queue-board data-url="{{ route('queue.data', array_filter(['kategori' => $category])) }}">
    <header class="page-head rise">
        <div>
            <h1 class="page-title">Antrian tiket IT</h1>
            <p class="queue-live">
                <span class="live-dot" aria-hidden="true"></span>
                Diperbarui otomatis, terakhir <time data-updated-at>{{ now()->format('H.i') }}</time>
            </p>
        </div>
        <a class="btn btn--primary shine" href="{{ route('tickets.create') }}">Buat tiket</a>
    </header>

    <div class="stats">
        @foreach ($stats as $key => $label)
            <div class="stat spotlight lift rise i-{{ $loop->iteration }}">
                <span class="stat-label">{{ $label }}</span>
                <span class="stat-value" data-stat="{{ $key }}" data-count-to="{{ $board['summary'][$key] }}">{{ $board['summary'][$key] }}</span>
            </div>
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
            <input class="input" type="search" id="cari" name="cari" value="{{ $search }}" placeholder="Cari nomor tiket" autocomplete="off" data-queue-search>
            <button class="btn btn--secondary" type="submit">Cari</button>
        </form>
    </div>

    <div class="queue-card">
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

        <x-empty-state title="Belum ada tiket aktif. Semua kendala sudah tertangani." data-queue-empty :hidden="$board['rows'] !== []">
            <a class="btn btn--secondary btn--sm" href="{{ route('tickets.create') }}">Buat tiket</a>
        </x-empty-state>

        <p class="queue-more" data-queue-more @if ($board['more'] === 0) hidden @endif>
            dan <span data-more-count>{{ $board['more'] }}</span> tiket lainnya
        </p>
    </div>

    <p class="queue-foot">Punya tiket di antrian? <a href="{{ route('tracking.lookup') }}">Cari tiket kamu</a> untuk melihat detail dan membalas.</p>

    <template data-row-template>@include('queue._row', ['row' => null])</template>
    <p class="sr-only" aria-live="polite" data-queue-announce></p>
    <div class="toast toast--sticky" role="alert" data-queue-error hidden>Gagal memperbarui, mencoba lagi…</div>
</div>
@endsection
