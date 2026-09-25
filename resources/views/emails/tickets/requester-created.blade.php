@extends('layouts.email')

@section('body')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;font-weight:800;">Halo {{ $ticket->requester_name }}, tiket kamu sudah kami terima.</h1>
    <p style="margin:0 0 20px;color:#5F6670;">
        Tim IT memproses tiket sesuai urutan antrian. Kamu akan menerima email setiap kali statusnya berubah.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background:#FEF4EE;border-radius:12px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0;font-size:13px;color:#5F6670;">Nomor tiket</p>
                <p style="margin:2px 0 0;font-size:24px;font-weight:800;letter-spacing:-0.5px;color:#B9501F;white-space:nowrap;">{{ $ticket->ticket_no }}</p>
            </td>
            <td align="right" style="padding:18px 20px;">
                <p style="margin:0;font-size:13px;color:#5F6670;">Posisi antrian</p>
                <p style="margin:2px 0 0;font-size:24px;font-weight:800;">{{ $position ?? '-' }}</p>
            </td>
        </tr>
    </table>

    @include('emails.tickets._details', ['rows' => [
        'Kategori' => $ticket->category->name,
        $ticket->typeFieldLabel() => $ticket->typeLabel(),
        'Status' => $ticket->status->label(),
        'Waktu masuk' => $ticket->created_at?->translatedFormat('d M Y, H:i'),
    ]])

    @include('emails.tickets._button', ['url' => $trackingUrl, 'label' => 'Lihat tiket'])

    <p style="margin:20px 0 0;font-size:13px;color:#5F6670;">Simpan nomor tiket ini kalau ingin menanyakan progres ke tim IT.</p>
@endsection
