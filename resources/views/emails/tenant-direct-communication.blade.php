<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; -webkit-font-smoothing: antialiased;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f1f5f9; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0;">

                    <!-- Barra superior de marca -->
                    <tr>
                        <td style="height: 6px; background: linear-gradient(90deg, #FF7A00 0%, #FB923C 50%, #F59E0B 100%); font-size: 0; line-height: 0;">&nbsp;</td>
                    </tr>

                    <!-- Hero Header oscuro -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 28px 32px; text-align: left;">
                            <div style="display: inline-block; background-color: rgba(255, 122, 0, 0.18); border: 1px solid rgba(255, 122, 0, 0.45); color: #fdba74; font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; padding: 5px 12px; border-radius: 999px; margin-bottom: 12px;">
                                TALLER FLOW &bull; {{ $tenant->name }}
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 800; line-height: 1.3; letter-spacing: -0.3px;">
                                {{ $subject }}
                            </h1>
                        </td>
                    </tr>

                    <!-- Contenido Principal -->
                    <tr>
                        <td style="padding: 32px;">
                            <div style="font-size: 15px; color: #334155; line-height: 1.65; white-space: pre-line;">{{ $body }}</div>

                            @if (! empty($actionUrl))
                                <div style="margin: 28px 0 16px 0; text-align: center;">
                                    <a href="{{ $actionUrl }}" target="_blank" rel="noopener" style="display: inline-block; background-color: #FF7A00; color: #ffffff; font-size: 15px; font-weight: 800; text-decoration: none; padding: 13px 28px; border-radius: 12px; box-shadow: 0 4px 14px rgba(255, 122, 0, 0.35);">
                                        {{ $actionLabel }} &rarr;
                                    </a>
                                </div>
                            @endif

                            <!-- Bloque de respuesta directa -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-top: 28px;">
                                <tr>
                                    <td style="padding: 16px 20px; text-align: center;">
                                        <p style="margin: 0; font-size: 13.5px; color: #475569; line-height: 1.5;">
                                            💬 ¿Tienes dudas o comentarios? <strong style="color: #0f172a;">Responde directamente a este correo</strong>
                                            @if (! empty($whatsappUrl))
                                                o escríbenos por <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" style="color: #ea580c; font-weight: 700; text-decoration: none;">WhatsApp</a>
                                            @endif
                                            y te ayudaremos de inmediato.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Pie del correo -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center;">
                            <p style="margin: 0; font-size: 13px; font-weight: 700; color: #0f172a;">
                                {{ $senderName ?? 'El equipo de Taller Flow' }}
                            </p>
                            <p style="margin: 6px 0 0 0; font-size: 11.5px; color: #94a3b8; line-height: 1.5;">
                                Mensaje enviado a {{ $recipientName }} ({{ $tenant->name }}) desde Taller Flow.
                            </p>
                            @if (! empty($trackingPixelUrl))
                                <img src="{{ $trackingPixelUrl }}" width="1" height="1" border="0" style="display:none;width:1px!important;height:1px!important;max-height:0;overflow:hidden;mso-hide:all;" alt="" />
                            @endif
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
