<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creamos una herramienta a tu medida</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; -webkit-font-smoothing: antialiased;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f1f5f9; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0;">

                    <tr>
                        <td style="height: 6px; background: linear-gradient(90deg, #FF7A00 0%, #FB923C 50%, #F59E0B 100%); font-size: 0; line-height: 0;">&nbsp;</td>
                    </tr>

                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 36px 32px 32px 32px; text-align: center;">
                            <div style="display: inline-block; background-color: rgba(255, 122, 0, 0.18); border: 1px solid rgba(255, 122, 0, 0.45); color: #fdba74; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; padding: 6px 14px; border-radius: 999px; margin-bottom: 16px;">
                                🛠️ HERRAMIENTAS A TU MEDIDA
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 900; line-height: 1.25; letter-spacing: -0.5px;">
                                Hola <span style="color: #FF7A00;">{{ $recipientName }}</span>, ¿qué le falta a tu taller?
                            </h1>
                            <p style="margin: 12px 0 0 0; color: #cbd5e1; font-size: 15px; line-height: 1.5;">
                                Si hay algo que hoy haces a mano o que no encuentras en el sistema, <strong style="color: #ffffff;">lo podemos construir para <span style="color: #FF7A00;">{{ $tenant->name }}</span></strong>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px;">

                            <p style="margin: 0 0 20px 0; font-size: 15px; color: #475569; line-height: 1.6;">
                                En Taller Flow seguimos mejorando la plataforma con la ayuda de los talleres que la usan. Cuéntanos qué necesitas y vemos cómo resolverlo: <strong style="color: #0f172a;">una herramienta pensada para tu forma de trabajar</strong>.
                            </p>

                            <div style="margin-bottom: 14px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #FF7A00;">
                                ✨ Ejemplos de lo que podemos hacer
                            </div>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 12px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">📄 Informes y documentos con tu formato</div>
                                        <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">Presupuestos, órdenes y reportes con la imagen y los datos que tú necesitas.</div>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 12px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">🔗 Integraciones con lo que ya usas</div>
                                        <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">Contabilidad, facturación, proveedores o cualquier sistema que hoy trabaja por separado.</div>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">⚙️ Procesos propios de tu taller</div>
                                        <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">Flujos, alertas o controles que se ajusten a cómo trabaja tu equipo.</div>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1px solid #fdba74; border-radius: 16px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px; text-align: center;">
                                        <h2 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #0f172a;">Contáctanos y cuéntanos tu idea</h2>
                                        <p style="margin: 0 0 18px 0; font-size: 14px; color: #475569; line-height: 1.55;">
                                            Responde este correo o escríbenos por WhatsApp. Sin compromiso: primero conversamos qué necesitas.
                                        </p>
                                        @if ($whatsappUrl)
                                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" style="display: inline-block; background-color: #FF7A00; color: #ffffff; font-size: 15px; font-weight: 800; text-decoration: none; padding: 14px 28px; border-radius: 12px; box-shadow: 0 4px 14px rgba(255, 122, 0, 0.35); margin: 0 4px 8px 4px;">
                                                Escribir por WhatsApp &rarr;
                                            </a>
                                        @endif
                                        <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode('Herramienta a medida para '.$tenant->name) }}" style="display: inline-block; background-color: {{ $whatsappUrl ? '#0f172a' : '#FF7A00' }}; color: #ffffff; font-size: 15px; font-weight: 800; text-decoration: none; padding: 14px 28px; border-radius: 12px; margin: 0 4px 8px 4px;">
                                            Enviar un correo &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border-radius: 12px;">
                                <tr>
                                    <td style="padding: 16px 20px; text-align: center;">
                                        <p style="margin: 0; font-size: 13.5px; color: #475569;">
                                            Mientras tanto, tu taller te espera en
                                            <a href="{{ $loginUrl }}" target="_blank" rel="noopener" style="color: #ea580c; font-weight: 700; text-decoration: none;">Taller Flow</a>.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center;">
                            <p style="margin: 0; font-size: 13px; font-weight: 700; color: #0f172a;">El equipo de Taller Flow</p>
                            <p style="margin: 6px 0 0 0; font-size: 11.5px; color: #94a3b8; line-height: 1.5;">
                                Recibes este correo por ser administrador de {{ $tenant->name }} en Taller Flow.<br>
                                <a href="{{ $unsubscribeUrl }}" style="color: #94a3b8; text-decoration: underline;">Darse de baja de estos correos</a>
                            </p>
                            <p style="margin: 6px 0 0 0; font-size: 12px; color: #94a3b8;">
                                © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
