<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f1b2d;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #dbe3ef;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#2856e6;background-image:linear-gradient(135deg,#1b3fb4,#2856e6);padding:24px 28px;color:#ffffff;">
                            <div style="font-size:20px;font-weight:800;letter-spacing:-0.3px;">CloudSpace Solutions</div>
                            <div style="margin-top:2px;font-size:13px;color:#dce5ff;">Coworking y salas de conferencias</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            @yield('contenido')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background:#f7f9fd;border-top:1px solid #dbe3ef;font-size:12px;line-height:1.5;color:#55657d;">
                            Correo enviado por {{ config('app.name') }} mediante SendGrid.<br>
                            Bucaramanga, Colombia.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
