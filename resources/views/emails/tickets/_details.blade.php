{{-- Isi dari pemohon selalu di-escape; nl2br hanya untuk baris baru. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;">
    @foreach ($rows as $label => $value)
        <tr>
            <td style="padding:9px 0;border-bottom:1px solid #E3E5E9;color:#5F6670;width:38%;vertical-align:top;">{{ $label }}</td>
            <td style="padding:9px 0;border-bottom:1px solid #E3E5E9;font-weight:700;vertical-align:top;">{{ $value }}</td>
        </tr>
    @endforeach
</table>

<p style="margin:0 0 6px;font-size:13px;color:#5F6670;">Deskripsi kendala</p>
<div style="padding:14px 16px;background:#F5F6F8;border-radius:8px;font-size:14px;line-height:1.6;">{!! nl2br(e($ticket->description)) !!}</div>
