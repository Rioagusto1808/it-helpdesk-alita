{{-- Email berbasis tabel + inline style (satu-satunya tempat hex di Blade) agar rapi di Outlook, Gmail, dan HP. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('helpdesk.app_name') }}</title>
</head>
<body style="margin:0;padding:0;background:#F5F6F8;font-family:'Plus Jakarta Sans','Segoe UI',Arial,sans-serif;color:#1B1F24;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F5F6F8;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border:1px solid #E3E5E9;border-radius:12px;overflow:hidden;">
                <tr><td style="height:6px;line-height:6px;font-size:0;background:#E97537;">&nbsp;</td></tr>
                <tr>
                    <td style="padding:28px 32px 4px;font-size:13px;font-weight:700;color:#5F6670;">{{ config('helpdesk.app_name') }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 32px 32px;font-size:15px;line-height:1.6;">
                        @yield('body')
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px;background:#F5F6F8;font-size:12px;line-height:1.5;color:#5F6670;">
                        Email ini dikirim otomatis oleh {{ config('helpdesk.app_name') }}.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
