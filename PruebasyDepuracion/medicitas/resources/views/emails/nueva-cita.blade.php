<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva cita agendada</title>
</head>
<body style="margin: 0; padding: 24px 12px; background: #f3f6f8; font-family: Arial, Helvetica, sans-serif; color: #1f2a37;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #dde5ea; border-radius: 12px;">
        <tr>
            <td style="padding: 20px 28px; background: #0f766e; border-radius: 12px 12px 0 0; color: #ffffff; font-size: 20px; font-weight: bold;">
                MediCitas
            </td>
        </tr>
        <tr>
            <td style="padding: 28px;">
                <p style="margin: 0 0 12px; font-size: 16px;">Hola, {{ $cita->medico->name }}:</p>
                <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.5;">
                    Tienes una nueva cita de <strong>{{ $cita->especialidad->value }}</strong> agendada por un paciente.
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; line-height: 1.5;">
                    <tr>
                        <td style="padding: 8px 0; color: #5b6b7b; width: 130px;">Paciente</td>
                        <td style="padding: 8px 0;"><strong>{{ $cita->paciente->name }}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5b6b7b;">Fecha y hora</td>
                        <td style="padding: 8px 0;"><strong>{{ ucfirst($cita->fecha_hora->translatedFormat('l j \d\e F \d\e Y, g:i a')) }}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5b6b7b;">Especialidad</td>
                        <td style="padding: 8px 0;">{{ $cita->especialidad->value }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5b6b7b; vertical-align: top;">Motivo</td>
                        <td style="padding: 8px 0;">{{ $cita->motivo }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #5b6b7b;">Estado</td>
                        <td style="padding: 8px 0;">{{ $cita->estado->etiqueta() }}</td>
                    </tr>
                </table>

                <p style="margin: 24px 0 0;">
                    <a href="{{ route('citas.index') }}" style="display: inline-block; padding: 10px 18px; background: #0f766e; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold;">Ver citas</a>
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 28px; border-top: 1px solid #dde5ea; color: #5b6b7b; font-size: 12px;">
                Correo automático de la clínica MediCitas (cita #{{ $cita->id }}).
            </td>
        </tr>
    </table>
</body>
</html>
