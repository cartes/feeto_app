<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Hemos recibido tu solicitud de prueba! - {{ config('app.name') }}</title>
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
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 36px 32px 32px 32px; text-align: center;">
                            <div style="display: inline-block; background-color: rgba(255, 122, 0, 0.18); border: 1px solid rgba(255, 122, 0, 0.45); color: #fdba74; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; padding: 6px 14px; border-radius: 999px; margin-bottom: 16px;">
                                🚀 SOLICITUD RECIBIDA CON ÉXITO
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 900; line-height: 1.25; letter-spacing: -0.5px;">
                                ¡Hola, <span style="color: #FF7A00;">{{ $trialRequest->name }}</span>!
                            </h1>
                            <p style="margin: 12px 0 0 0; color: #cbd5e1; font-size: 15px; line-height: 1.5;">
                                Recibimos tu solicitud de prueba gratuita para <strong style="color: #ffffff;">{{ $trialRequest->business_name }}</strong> en <strong style="color: #ffffff;">{{ config('app.name') }}</strong>.
                            </p>
                        </td>
                    </tr>

                    <!-- Contenido Principal -->
                    <tr>
                        <td style="padding: 32px;">

                            <!-- Estado de la solicitud -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 5px solid #FF7A00; border-radius: 14px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 20px 22px;">
                                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #FF7A00; margin-bottom: 6px;">
                                            ⏱️ PRÓXIMO PASO
                                        </div>
                                        <p style="margin: 0; font-size: 14.5px; color: #334155; line-height: 1.55;">
                                            Nuestro equipo está revisando tus datos y te enviaremos a este mismo correo tus <strong style="color: #0f172a;">credenciales de acceso</strong> para activar tus 14 días de prueba gratuita.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Tarjeta Destacada: Futura Página de Agendamiento -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1px solid #fdba74; border-radius: 16px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #c2410c; margin-bottom: 6px;">
                                            📅 TU FUTURA PÁGINA DE AGENDAMIENTO PARA CLIENTES
                                        </div>
                                        <h2 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                            Enlace exclusivo para los clientes de {{ $trialRequest->business_name }}
                                        </h2>
                                        <p style="margin: 0 0 16px 0; font-size: 14px; color: #475569; line-height: 1.55;">
                                            Una vez aprobada tu solicitud, tus clientes podrán reservar horas en línea desde tu enlace público y tú recibirás notificaciones automáticas por correo y en tu panel:
                                        </p>

                                        <div style="background-color: #ffffff; border: 1px dashed #fb923c; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; text-align: center;">
                                            <a href="{{ $bookingUrl }}" target="_blank" rel="noopener" style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; font-weight: 700; color: #ea580c; text-decoration: none; word-break: break-all;">
                                                {{ $bookingUrl }}
                                            </a>
                                        </div>

                                        <div style="text-align: center;">
                                            <a href="{{ $bookingUrl }}" target="_blank" rel="noopener" style="display: inline-block; background-color: #0f172a; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none; padding: 11px 22px; border-radius: 10px;">
                                                Ver página de agendamiento &#8599;
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Sección Tutorial Paso a Paso -->
                            <div style="margin-bottom: 18px;">
                                <span style="display: inline-block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.4px; color: #FF7A00; background-color: #fff7ed; border: 1px solid #fed7aa; padding: 4px 10px; border-radius: 6px; margin-bottom: 8px;">
                                    ✨ Pequeño tutorial
                                </span>
                                <h2 style="margin: 0; font-size: 20px; font-weight: 900; color: #0f172a; letter-spacing: -0.3px;">
                                    ¿Qué hace el sistema? Conoce todo lo que tendrás disponible
                                </h2>
                            </div>

                            <!-- Paso 1 -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 12px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td width="44" valign="top">
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #eff6ff; border: 1px solid #bfdbfe; text-align: center; font-size: 16px; font-weight: 900; color: #2563eb;">
                                                        1
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        📆 Página de agendamiento para tus clientes
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Tus clientes podrán agendar horas de atención, indicar qué le ocurre a su vehículo (Pre-Check digital) y solicitar cotizaciones. Cada vez que agenden, recibirás una notificación por correo y en tu panel.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Paso 2 -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 12px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td width="44" valign="top">
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #ecfdf5; border: 1px solid #a7f3d0; text-align: center; font-size: 16px; font-weight: 900; color: #059669;">
                                                        2
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        📸 Recepción inteligente con IA
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Ingresa vehículos tomando una foto a la patente para autocompletar datos, registrar checklist de ingreso y respaldar fotos del estado inicial.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Paso 3 -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 12px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td width="44" valign="top">
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #f5f3ff; border: 1px solid #ddd6fe; text-align: center; font-size: 16px; font-weight: 900; color: #7c3aed;">
                                                        3
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        📋 Órdenes de Trabajo (OT) y Tablero Kanban
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Organiza los vehículos en el taller por etapas (Recepción, Diagnóstico, En Reparación, Listo para Entrega) y asigna repuestos y mano de obra.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Paso 4 -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 12px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td width="44" valign="top">
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #fff7ed; border: 1px solid #fed7aa; text-align: center; font-size: 16px; font-weight: 900; color: #ea580c;">
                                                        4
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        💬 Cotizaciones digitales y seguimiento en vivo
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Envía presupuestos a tus clientes con un link de seguimiento en tiempo real para que aprueben en línea y reciban avisos automáticos.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Paso 5 -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td width="44" valign="top">
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #fefce8; border: 1px solid #fde047; text-align: center; font-size: 16px; font-weight: 900; color: #ca8a04;">
                                                        5
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        📊 Control de inventario y reportes
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Gestiona tu stock de repuestos, catálogo de servicios, historial de clientes y reportes de ventas.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Footer de ayuda -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border-radius: 12px; padding: 16px 20px; text-align: center;">
                                <tr>
                                    <td>
                                        <p style="margin: 0; font-size: 13.5px; color: #475569;">
                                            💬 Si tienes alguna duda, <strong style="color: #0f172a;">puedes responder directamente a este correo</strong>.
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
                                ¡Un saludo del equipo de {{ config('app.name') }}!
                            </p>
                            <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8;">
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
