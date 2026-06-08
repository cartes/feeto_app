# Nueva solicitud recibida desde la Landing Page

Hola,

Has recibido una nueva consulta a través de tu página de contacto en **TallerFlow**.

## Información del Cliente
- **Nombre:** {{ $data['name'] }}
- **Correo:** {{ $data['email'] }}
- **Teléfono:** {{ $data['phone'] }}
- **Tipo de Solicitud:** {{ ($data['type'] ?? 'general') === 'quote' ? 'Solicitud de Cotización (Deseo cotizar)' : 'Consulta General' }}

@if(($data['type'] ?? 'general') === 'quote')
## Detalles del Vehículo
- **Patente:** {{ $data['plate'] ?? 'No especificada' }}
- **Marca:** {{ $data['brand'] ?? 'No especificada' }}
- **Modelo:** {{ $data['model'] ?? 'No especificada' }}
- **Año:** {{ $data['year'] ?? 'No especificado' }}
@endif

## Mensaje / Detalles
{{ $data['message'] }}

---
Este correo fue enviado automáticamente por la plataforma Feeto. Puedes responder a este correo para comunicarte directamente con el cliente en su casilla: {{ $data['email'] }}.
