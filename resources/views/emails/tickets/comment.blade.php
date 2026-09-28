@extends('layouts.email')

@section('body')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:800;">Ada balasan dari tim IT</h1>
    <p style="margin:0 0 20px;color:#5F6670;">
        {{ $comment->user->name ?? 'Tim IT' }} membalas tiket <strong style="color:#B9501F;white-space:nowrap;">{{ $ticket->ticket_no }}</strong>.
        @if ($newStatus)
            Status tiket sekarang <strong style="color:#1B1F24;">{{ $newStatus->label() }}</strong>.
        @endif
    </p>

    <div style="padding:14px 16px;background:#F5F6F8;border-radius:8px;font-size:14px;line-height:1.6;">{!! nl2br(e($comment->body)) !!}</div>

    @if ($comment->attachments->isNotEmpty())
        <p style="margin:14px 0 0;font-size:13px;color:#5F6670;">
            Lampiran terlampir di email ini: {{ $comment->attachments->pluck('original_name')->join(', ') }}
        </p>
    @endif

    @include('emails.tickets._button', ['url' => $trackingUrl, 'label' => 'Balas'])
@endsection
