<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Baja de correos | {{ config('app.name') }}</title>
</head>
<body style="margin: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155;">
    <div style="max-width: 480px; margin: 12vh auto; padding: 0 16px;">
        <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; text-align: center;">
            <h1 style="margin: 0 0 12px 0; font-size: 22px; color: #0f172a;">Listo, no te enviaremos más novedades</h1>
            <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #475569;">
                {{ $tenant->name }} ya no recibirá correos promocionales de {{ config('app.name') }}.
                Seguirás recibiendo los avisos propios del servicio, como citas y órdenes de trabajo.
            </p>
        </div>
    </div>
</body>
</html>
