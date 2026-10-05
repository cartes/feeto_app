<x-mail::message>
# ¡Recibimos tu solicitud de cita!

Hola **{{ $appointment->customer_name }}**,

Tu hora de atención en **{{ $tenant->name }}** está pendiente de confirmación. El taller se pondrá en contacto contigo para confirmar la disponibilidad.

## Resumen de tu solicitud

- **Fecha y Hora:** {{ $appointment->appointment_date->locale('es_CL')->translatedFormat('d \d\e F, Y \a \l\a\s H:i \h\r\s') }}
- **Patente:** {{ $appointment->plate }}
- **Taller:** {{ $tenant->name }}
@if($tenant->seo_address)
- **Dirección:** {{ $tenant->seo_address }}
@endif
@if($tenant->whatsapp_number)
- **Teléfono:** {{ $tenant->whatsapp_number }}
@endif

@if($appointment->pre_check_notes)
## Notas que compartiste
> {{ $appointment->pre_check_notes }}
@endif

---
**Espera la confirmación del taller antes de asistir.**

Si necesitas reagendar o tienes alguna consulta, contáctanos directamente al taller.

---
Este correo fue enviado automáticamente por la plataforma TallerFlow.
</x-mail::message>
