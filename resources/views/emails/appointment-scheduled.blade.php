# Nueva cita agendada en tu taller

Hola,

Se ha agendado una nueva hora de atención en **TallerFlow**.

## Información de la Cita
- **Cliente:** {{ $appointment->customer_name }}
- **Teléfono:** {{ $appointment->phone }}
- **Patente:** {{ $appointment->plate }}
- **Fecha y Hora:** {{ $appointment->appointment_date->locale('es_CL')->translatedFormat('d \d\e F, Y \a \l\a\s H:i \h\r\s') }}

@if($appointment->pre_check_notes)
## Notas de Pre-evaluación
{{ $appointment->pre_check_notes }}
@endif

---
Este correo fue enviado automáticamente por la plataforma Taller Flow.
