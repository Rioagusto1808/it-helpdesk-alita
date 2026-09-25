@extends('layouts.email')

@section('body')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:800;">Tiket baru masuk</h1>
    <p style="margin:0 0 20px;color:#5F6670;">
        {{ $ticket->requester_name }} melaporkan kendala {{ $ticket->category->name }}. Balas email ini untuk langsung menghubungi pemohon.
    </p>

    <p style="margin:0 0 20px;font-size:26px;font-weight:800;letter-spacing:-0.5px;color:#B9501F;white-space:nowrap;">{{ $ticket->ticket_no }}</p>

    @include('emails.tickets._details', ['rows' => [
        'Kategori' => $ticket->category->name,
        $ticket->typeFieldLabel() => $ticket->typeLabel(),
        'Pemohon' => $ticket->requester_name,
        'Email' => $ticket->requester_email,
        'Waktu masuk' => $ticket->created_at?->translatedFormat('d M Y, H:i'),
        'Posisi antrian' => $position ?? '-',
        'Lampiran' => $ticket->attachments->isEmpty() ? 'Tidak ada' : $ticket->attachments->pluck('original_name')->join(', '),
    ]])

    @if ($ticket->attachments->isNotEmpty())
        <p style="margin:16px 0 0;font-size:13px;color:#5F6670;">Lampiran tidak ditempel di email. Buka lewat panel admin.</p>
    @endif

    @include('emails.tickets._button', ['url' => $adminUrl, 'label' => 'Buka di panel admin'])
@endsection
