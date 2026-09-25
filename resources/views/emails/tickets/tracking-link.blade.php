@extends('layouts.email')

@section('body')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:800;">Link tiket kamu</h1>
    <p style="margin:0 0 4px;color:#5F6670;">Gunakan tombol di bawah untuk melihat status, riwayat, dan membalas tiket</p>
    <p style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px;color:#B9501F;white-space:nowrap;">{{ $ticket->ticket_no }}</p>

    @include('emails.tickets._button', ['url' => $trackingUrl, 'label' => 'Lihat tiket'])

    <p style="margin:20px 0 0;font-size:13px;color:#5F6670;">
        Link berlaku {{ config('helpdesk.tracking_link_days') }} hari. Kalau kamu tidak meminta link ini, abaikan saja email ini.
    </p>
@endsection
