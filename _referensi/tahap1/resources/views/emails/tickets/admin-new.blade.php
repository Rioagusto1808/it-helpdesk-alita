@php($accent = $ticket->accentColor())
@extends('emails.tickets.layout')

@section('body')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:800;">Tiket baru masuk</h1>
    <p style="margin:0 0 22px;color:#5A6474;">
        {{ $ticket->requester_name }} melaporkan kendala {{ $ticket->category->name }}.
        Balas email ini untuk langsung menghubungi pemohon.
    </p>

    <p style="margin:0 0 20px;font-size:26px;font-weight:800;letter-spacing:-0.5px;color:{{ $accent }};">
        {{ $ticket->ticket_no }}
    </p>

    @include('emails.tickets._details', [
        'rows' => [
            'Kategori' => $ticket->category->name,
            $ticket->typeFieldLabel() => $ticket->typeLabel(),
            'Pemohon' => $ticket->requester_name,
            'Email' => $ticket->requester_email,
            'Waktu masuk' => $ticket->created_at->timezone(config('app.timezone'))->format('d M Y, H:i'),
            'Posisi antrian' => $ticket->queuePosition() ?? '-',
            'Lampiran' => $ticket->attachments->isEmpty()
                ? 'Tidak ada'
                : $ticket->attachments->map(fn ($a) => $a->original_name.' ('.$a->humanSize().')')->join(', '),
        ],
    ])

    @if ($ticket->attachments->isNotEmpty())
        <p style="margin:16px 0 0;font-size:13px;color:#5A6474;">
            Lampiran tidak ditempel di email demi keamanan. Buka lewat dashboard admin.
        </p>
    @endif
@endsection
