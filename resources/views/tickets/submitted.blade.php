@extends('layouts.public')

@section('title', 'Tiket terkirim')

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
<section class="done" aria-labelledby="done-title">
    <div class="confetti-host" data-confetti aria-hidden="true"></div>

    <div class="done-card card">
        <div class="done-head">
            <span class="done-icon" aria-hidden="true">
                <svg class="draw-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            </span>
            <h1 id="done-title" class="done-title rise i-1">Tiket kamu sudah masuk antrian.</h1>
            <p class="done-lede rise i-2">Konfirmasi sudah dikirim ke <strong>{{ $ticket->requester_email }}</strong>.</p>
        </div>

        <div class="stub">
            <span class="stub-label">Nomor tiket</span>
            <div class="stub-row">
                <span class="stub-number" id="ticket-no">{{ $ticket->ticket_no }}</span>
                <button type="button" class="btn btn--sm btn--copy" data-copy="ticket-no">
                    <x-icon name="copy" class="icon--sm" /> <span data-label>Salin nomor</span>
                </button>
                <span class="sr-only" aria-live="polite" data-copy-status></span>
            </div>
        </div>

        <dl class="done-meta">
            <div class="rise i-3">
                <dt>Posisi antrian</dt>
                <dd class="done-pos">
                    @if ($position)
                        <span data-count-to="{{ $position }}">{{ $position }}</span>
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div class="rise i-4">
                <dt>Status</dt>
                <dd><x-status-badge :status="$ticket->status" class="badge--lg" /></dd>
            </div>
            <div class="rise i-5">
                <dt>Kategori</dt>
                <dd>{{ $ticket->category->name }}</dd>
            </div>
            <div class="rise i-6">
                <dt>{{ $ticket->typeFieldLabel() }}</dt>
                <dd>{{ $ticket->typeLabel() }}</dd>
            </div>
        </dl>

        <p class="done-note"><x-icon name="mail" class="icon--sm" /> Simpan nomor tiket ini. Setiap perubahan status akan dikirim ke email kamu.</p>

        <div class="done-actions">
            <a class="btn btn--primary shine" href="{{ route('queue.index') }}">Lihat antrian <x-icon name="arrow-right" class="btn-arrow" /></a>
            <a class="btn btn--secondary" href="{{ route('tickets.create') }}"><x-icon name="plus" /> Buat tiket lain</a>
        </div>
    </div>
</section>
@endsection
