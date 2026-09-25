{{-- Tabel detail tiket, dipakai di email admin & pemohon. Semua isi di-escape oleh {{ }}. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;">
    @foreach ($rows as $label => $value)
        <tr>
            <td style="padding:9px 0;border-bottom:1px solid #E4E8ED;color:#5A6474;width:38%;vertical-align:top;">{{ $label }}</td>
            <td style="padding:9px 0;border-bottom:1px solid #E4E8ED;font-weight:700;vertical-align:top;">{{ $value }}</td>
        </tr>
    @endforeach
</table>

<p style="margin:0 0 6px;font-size:13px;color:#5A6474;">Deskripsi kendala</p>
<div style="padding:14px 16px;background:#F6F7F9;border-radius:10px;font-size:14px;line-height:1.6;white-space:normal;">
    {!! nl2br(e($ticket->description)) !!}
</div>
