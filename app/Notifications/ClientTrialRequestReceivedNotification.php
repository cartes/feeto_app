<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use App\Models\TrialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientTrialRequestReceivedNotification extends Notification
{
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación.
     */
    public function __construct(public readonly TrialRequest $trialRequest) {}

    /**
     * Obtiene los canales de entrega de la notificación.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Obtiene la representación por correo de la notificación.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $slug = $this->trialRequest->tenant?->slug
            ?? Tenant::generateUniqueSlug($this->trialRequest->business_name);
        $bookingUrl = route('taller.landing', ['tenantBySlug' => $slug]);

        return (new MailMessage)
            ->subject('¡Hemos recibido tu solicitud de prueba! - '.config('app.name'))
            ->greeting('¡Hola '.$this->trialRequest->name.'!')
            ->line('Gracias por tu interés en **'.config('app.name').'**.')
            ->line('Hemos recibido correctamente tu solicitud de prueba gratuita para tu negocio **'.$this->trialRequest->business_name.'**.')
            ->line('Nuestro equipo está revisando tus datos y te contactaremos a la brevedad con tus credenciales de acceso.')
            ->line('---')
            ->line('**¿Qué hace el sistema? (Pequeño tutorial):**')
            ->line('1. **Página de agendamiento para tus clientes:** Tendrás una página pública propia donde tus clientes podrán agendar horas de atención, indicar qué le ocurre a su vehículo (Pre-Check digital) y solicitar cotizaciones. Cada vez que un cliente agende, recibirás una notificación por correo y en tu panel.')
            ->line('2. **Recepción inteligente con IA:** Ingresa vehículos tomando una foto a la patente para autocompletar datos, registrar checklist de ingreso y respaldar fotos del estado inicial.')
            ->line('3. **Órdenes de Trabajo (OT) y Tablero Kanban:** Organiza los vehículos en el taller por etapas (Recepción, Diagnóstico, En Reparación, Listo para Entrega) y asigna repuestos y mano de obra.')
            ->line('4. **Cotizaciones digitales y seguimiento en vivo:** Envía presupuestos a tus clientes con un link de seguimiento en tiempo real para que aprueben en línea y reciban avisos automáticos.')
            ->line('5. **Control de inventario y reportes:** Gestiona tu stock de repuestos, catálogo de servicios, historial de clientes y reportes de ventas.')
            ->line('---')
            ->line('**Tu futura página de agendamiento para clientes:**')
            ->line('Una vez aprobada tu solicitud, tus clientes podrán agendar horas directamente en:')
            ->line("[{$bookingUrl}]({$bookingUrl})")
            ->action('Ver página de agendamiento', $bookingUrl)
            ->line('Si tienes alguna duda, puedes responder directamente a este correo.')
            ->salutation('¡Un saludo del equipo de '.config('app.name').'!');
    }

    /**
     * Obtiene la representación en arreglo de la notificación.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
