@extends('layouts.public')

@section('title', 'Tiket terkirim')

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
<section class="done rise" aria-labelledby="done-title">
    <div class="done-head">
        <svg class="done-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
        <h1 id="done-title" class="done-title">Tiket kamu sudah masuk antrian.</h1>
        <p class="done-lede">Konfirmasi sudah dikirim ke <strong>{{ $ticket->requester_email }}</strong>.</p>
    </div>

    <div class="done-number">
        <span class="done-number-label">Nomor tiket</span>
        <span class="done-number-value" id="ticket-no">{{ $ticket->ticket_no }}</span>
        <button type="button" class="btn btn--secondary btn--sm" data-copy="ticket-no">Salin nomor</button>
        <span class="sr-only" aria-live="polite" data-copy-status></span>
    </div>

    <dl class="done-meta">
        <div>
            <dt>Posisi antrian</dt>
            <dd class="done-pos">
                @if ($position)
                    <span data-count-to="{{ $position }}">{{ $position }}</span>
                @else
                    -
                @endif
            </dd>
        </div>
        <div>
            <dt>Status</dt>
            <dd><x-status-badge :status="$ticket->status" /></dd>
        </div>
        <div>
            <dt>Kategori</dt>
            <dd>{{ $ticket->category->name }}</dd>
        </div>
        <div>
            <dt>{{ $ticket->typeFieldLabel() }}</dt>
            <dd>{{ $ticket->typeLabel() }}</dd>
        </div>
    </dl>

    <p class="done-note">Simpan nomor tiket ini. Setiap perubahan status akan dikirim ke email kamu.</p>

    <div class="done-actions">
        <a class="btn btn--primary shine" href="{{ route('queue.index') }}">Lihat antrian</a>
        <a class="btn btn--secondary" href="{{ route('tickets.create') }}">Buat tiket lain</a>
    </div>
</section>
@endsection
