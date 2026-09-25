@extends('layouts.email')

@section('body')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:800;">Ada balasan dari tim IT</h1>
    <p style="margin:0 0 20px;color:#5F6670;">
        {{ $comment->user->name ?? 'Tim IT' }} membalas tiket <strong style="color:#B9501F;white-space:nowrap;">{{ $ticket->ticket_no }}</strong>.
    </p>

    <div style="padding:14px 16px;background:#F5F6F8;border-radius:8px;font-size:14px;line-height:1.6;">{!! nl2br(e($comment->body)) !!}</div>

    @include('emails.tickets._button', ['url' => $trackingUrl, 'label' => 'Balas'])
@endsection
