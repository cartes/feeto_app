<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guía rápida y tutoriales para {{ $tenant->name }}</title>
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
                                🚀 GUÍA RÁPIDA Y TUTORIALES
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 900; line-height: 1.25; letter-spacing: -0.5px;">
                                Hola <span style="color: #FF7A00;">{{ $recipientName }}</span>, sácale el máximo provecho a <span style="color: #FF7A00;">{{ $tenant->name }}</span>
                            </h1>
                            <p style="margin: 12px 0 0 0; color: #cbd5e1; font-size: 15px; line-height: 1.5;">
                                Te compartimos una guía práctica paso a paso y el enlace público de agendamiento de <strong style="color: #ffffff;">{{ $tenant->name }}</strong> para que automatices tu taller hoy mismo.
                            </p>
                        </td>
                    </tr>

                    <!-- Contenido Principal -->
                    <tr>
                        <td style="padding: 32px;">

                            <!-- Tarjeta de Acceso al Panel y Centro de Tutoriales -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 5px solid #FF7A00; border-radius: 14px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 22px 24px;">
                                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #FF7A00; margin-bottom: 12px;">
                                            🔐 TU ACCESO DIRECTO A TALLER FLOW
                                        </div>

                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td style="padding: 6px 0; font-size: 14px; color: #64748b; width: 150px; font-weight: 600;">Taller activo:</td>
                                                <td style="padding: 6px 0; font-size: 14px; color: #0f172a; font-weight: 700;">{{ $tenant->name }}</td>
                                            </tr>
                                            @if (! empty($recipientEmail))
                                                <tr>
                                                    <td style="padding: 6px 0; font-size: 14px; color: #64748b; font-weight: 600;">Correo de ingreso:</td>
                                                    <td style="padding: 6px 0; font-size: 14px; color: #0f172a; font-weight: 700;">{{ $recipientEmail }}</td>
                                                </tr>
                                            @endif
                                        </table>

                                        <!-- Botones de acción -->
                                        <div style="margin-top: 18px; text-align: center;">
                                            <a href="{{ $loginUrl }}" target="_blank" rel="noopener" style="display: inline-block; background-color: #FF7A00; color: #ffffff; font-size: 15px; font-weight: 800; text-decoration: none; padding: 13px 26px; border-radius: 12px; box-shadow: 0 4px 14px rgba(255, 122, 0, 0.35); margin: 0 4px 8px 4px;">
                                                Ingresar a mi taller &rarr;
                                            </a>
                                            <a href="{{ $tutorialsUrl }}" target="_blank" rel="noopener" style="display: inline-block; background-color: #0f172a; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 13px 22px; border-radius: 12px; margin: 0 4px 8px 4px;">
                                                Ver centro de tutoriales &#8599;
                                            </a>
                                        </div>
                                        <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b; text-align: center;">
                                            ¿Olvidaste tu contraseña? Puedes restablecerla en cualquier momento desde la pantalla de inicio de sesión.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Tarjeta Destacada: Link de Agendamiento Público -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1px solid #fdba74; border-radius: 16px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #c2410c; margin-bottom: 6px;">
                                            📅 ENLACE A TU PÁGINA PÚBLICA DE AGENDAMIENTO
                                        </div>
                                        <h2 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                            Tus clientes ya pueden reservar horas en línea
                                        </h2>
                                        <p style="margin: 0 0 16px 0; font-size: 14px; color: #475569; line-height: 1.55;">
                                            Comparte este link en tu WhatsApp, Instagram o sitio web para que los clientes de <strong style="color: #0f172a;">{{ $tenant->name }}</strong> agenden citas o soliciten cotizaciones. <strong style="color: #9a3412;">Cada vez que agenden, recibirás un aviso inmediato por correo y en la campana del panel.</strong>
                                        </p>

                                        <div style="background-color: #ffffff; border: 1px dashed #fb923c; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; text-align: center;">
                                            <a href="{{ $bookingUrl }}" target="_blank" rel="noopener" style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; font-weight: 700; color: #ea580c; text-decoration: none; word-break: break-all;">
                                                {{ $bookingUrl }}
                                            </a>
                                        </div>

                                        <div style="text-align: center;">
                                            <a href="{{ $bookingUrl }}" target="_blank" rel="noopener" style="display: inline-block; background-color: #0f172a; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none; padding: 11px 22px; border-radius: 10px;">
                                                Ver mi página de agendamiento &#8599;
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
                                    ¿Qué hace Taller Flow y cómo aprovecharlo?
                                </h2>
                                <p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b;">
                                    Sigue estos 5 pasos para automatizar la operación de tu taller desde hoy:
                                </p>
                            </div>

                            <!-- Paso 1 -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 12px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td width="44" valign="top">
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #fff7ed; border: 1px solid #fed7aa; text-align: center; font-size: 16px; font-weight: 900; color: #ea580c;">
                                                        1
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        ⚙️ Configura los datos de tu taller
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        En <strong>Configuración</strong> puedes personalizar tu logo, color de marca, dirección, teléfono, WhatsApp, correo de notificaciones y horarios de atención.
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
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #eff6ff; border: 1px solid #bfdbfe; text-align: center; font-size: 16px; font-weight: 900; color: #2563eb;">
                                                        2
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        📆 Agendamiento en línea para tus clientes
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Tus clientes reservan horas, envían síntomas del vehículo (Pre-Check digital) o piden cotizaciones desde tu página pública. Recibirás alertas inmediatas por correo y en tu panel.
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
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #ecfdf5; border: 1px solid #a7f3d0; text-align: center; font-size: 16px; font-weight: 900; color: #059669;">
                                                        3
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        📸 Recepción inteligente de vehículos
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Registra el ingreso del vehículo con lectura rápida de patente por cámara/IA, checklist de recepción y fotografías del estado inicial.
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
                                                    <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; background-color: #f5f3ff; border: 1px solid #ddd6fe; text-align: center; font-size: 16px; font-weight: 900; color: #7c3aed;">
                                                        4
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                                        📋 Órdenes de Trabajo (OT) y Tablero Kanban
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Controla cada etapa (Recepción, Diagnóstico, En Reparación, Listo para Entrega) y envía presupuestos digitales con seguimiento en vivo para que el cliente apruebe en línea.
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
                                                        📊 Inventario, clientes y reportes
                                                    </div>
                                                    <div style="font-size: 13.5px; color: #475569; line-height: 1.5;">
                                                        Administra tus repuestos y mano de obra con alertas de stock mínimo y revisa estadísticas comerciales de tu taller en tiempo real.
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
                                            💬 ¿Tienes alguna duda o necesitas ayuda para configurar tu taller?<br>
                                            <strong style="color: #0f172a;">Responde directamente a este correo</strong>
                                            @if (! empty($whatsappUrl))
                                                o escríbenos por <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" style="color: #ea580c; font-weight: 700; text-decoration: none;">WhatsApp</a>
                                            @endif
                                            y nuestro equipo te acompañará paso a paso.
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
                                El equipo de Taller Flow
                            </p>
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

