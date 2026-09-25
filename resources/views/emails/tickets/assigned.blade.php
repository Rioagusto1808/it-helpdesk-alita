@extends('layouts.email')

@section('body')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:800;">Tiket di-assign ke kamu</h1>
    <p style="margin:0 0 20px;font-size:24px;font-weight:800;letter-spacing:-0.5px;color:#B9501F;white-space:nowrap;">{{ $ticket->ticket_no }}</p>

    @include('emails.tickets._details', ['rows' => [
        'Kategori' => $ticket->category->name,
        $ticket->typeFieldLabel() => $ticket->typeLabel(),
        'Pemohon' => $ticket->requester_name,
        'Prioritas' => $ticket->priority->label(),
        'Status' => $ticket->status->label(),
    ]])

    @include('emails.tickets._button', ['url' => $adminUrl, 'label' => 'Buka di panel admin'])
@endsection
