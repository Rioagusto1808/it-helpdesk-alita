@extends('layouts.email')

@section('body')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:800;">Status tiket kamu berubah</h1>
    <p style="margin:0 0 20px;color:#5F6670;">
        Tiket <strong style="color:#B9501F;white-space:nowrap;">{{ $ticket->ticket_no }}</strong>
        berubah dari {{ $previousStatus->label() }} menjadi <strong style="color:#1B1F24;">{{ $newStatus->label() }}</strong>.
    </p>

    @if ($note)
        <p style="margin:0 0 6px;font-size:13px;color:#5F6670;">Catatan dari tim IT</p>
        <div style="padding:14px 16px;background:#F5F6F8;border-radius:8px;font-size:14px;line-height:1.6;">{!! nl2br(e($note)) !!}</div>
    @endif

    @include('emails.tickets._button', ['url' => $trackingUrl, 'label' => 'Lihat tiket'])
@endsection
