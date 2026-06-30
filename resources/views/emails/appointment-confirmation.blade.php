# ¡Tu cita está confirmada! ✅

Hola **{{ $appointment->customer_name }}**,

Tu hora de atención en **{{ $tenant->name }}** ha sido recibida y confirmada.

## Resumen de tu cita

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
**Te recomendamos llegar 5 minutos antes de tu cita.**

Si necesitas reagendar o tienes alguna consulta, contáctanos directamente al taller.

---
Este correo fue enviado automáticamente por la plataforma Feeto.
