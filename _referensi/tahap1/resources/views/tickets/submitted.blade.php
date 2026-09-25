@extends('layouts.app')

@section('title', 'Tiket terkirim')

@section('content')
<div class="shell shell-narrow">
    <main class="panel done" data-accent="{{ $ticket->category->code }}">
        <p class="brand">{{ config('helpdesk.app_name') }}</p>
        <h1>Tiket kamu sudah masuk antrian.</h1>

        <div class="ticket-box">
            <span class="ticket-caption">Nomor tiket</span>
            <span class="ticket-no" id="ticket-no">{{ $ticket->ticket_no }}</span>
            <button type="button" class="link-btn" data-copy="#ticket-no">Salin nomor</button>
        </div>

        <dl class="meta">
            <div><dt>Posisi antrian</dt><dd>{{ $ticket->queuePosition() ?? '-' }}</dd></div>
            <div><dt>Status</dt><dd>{{ $ticket->status->label() }}</dd></div>
            <div><dt>Kategori</dt><dd>{{ $ticket->category->name }}</dd></div>
            <div><dt>{{ $ticket->typeFieldLabel() }}</dt><dd>{{ $ticket->typeLabel() }}</dd></div>
        </dl>

        <p class="note">
            Konfirmasi sudah dikirim ke <strong>{{ $ticket->requester_email }}</strong>.
            Simpan nomor tiket ini kalau ingin menanyakan progres ke tim IT.
        </p>

        <a href="{{ route('tickets.create') }}" class="btn btn-ghost">Buat tiket lain</a>
    </main>
</div>
@endsection
