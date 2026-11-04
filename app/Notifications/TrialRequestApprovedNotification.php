<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use App\Models\TrialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TrialRequest $trialRequest,
        public readonly Tenant $tenant,
        public readonly string $temporaryPassword,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trialEndsAt = $this->tenant->subscription_ends_at?->format('d/m/Y') ?? '—';
        $bookingUrl = route('taller.landing', ['tenantBySlug' => $this->tenant->slug]);

        return (new MailMessage)
            ->subject("[Taller Flow] ¡Tu prueba gratuita de {$this->tenant->name} fue aprobada!")
            ->greeting("¡Hola, {$this->trialRequest->name}!")
            ->line("Tu solicitud de prueba gratuita para **{$this->tenant->name}** fue aprobada.")
            ->line('Ya puedes ingresar a la plataforma con estos datos de acceso:')
            ->line("**Correo:** {$this->trialRequest->email}")
            ->line("**Contraseña provisional:** {$this->temporaryPassword}")
            ->line("Tu prueba gratuita estará activa hasta el **{$trialEndsAt}** (14 días).")
            ->action('Ingresar a mi cuenta', route('login'))
            ->line('---')
            ->line('**¿Qué hace Taller Flow y cómo aprovecharlo? (Pequeño tutorial):**')
            ->line('1. **Configura los datos de tu taller:** Al ingresar por primera vez, completa la bienvenida rápida con tu dirección, teléfono, WhatsApp y horarios de atención.')
            ->line('2. **Agendamiento en línea para tus clientes:** Comparte el enlace de tu página pública para que tus clientes reserven horas, envíen síntomas del vehículo (Pre-Check digital) o pidan cotizaciones. Cada vez que un cliente agende una hora, recibirás una notificación por correo y una alerta en el panel.')
            ->line('3. **Recepción inteligente de vehículos:** Registra el ingreso del vehículo con lectura rápida de patente por cámara/IA, checklist de recepción y fotografías del estado inicial.')
            ->line('4. **Órdenes de Trabajo (OT) y Tablero Kanban:** Controla el avance de cada vehículo (Recepción, Diagnóstico, En Reparación, Listo para Entrega) y envía presupuestos digitales con seguimiento en tiempo real para que el cliente apruebe en línea.')
            ->line('5. **Inventario, clientes y reportes:** Administra tus repuestos y servicios con alertas de stock mínimo y revisa estadísticas comerciales de tu taller.')
            ->line('---')
            ->line('**Enlace a tu página pública de agendamiento:**')
            ->line("Comparte este link en tu WhatsApp, redes sociales o sitio web para que los clientes de **{$this->tenant->name}** puedan agendar horas directamente:")
            ->line("[{$bookingUrl}]({$bookingUrl})")
            ->line('Te recomendamos cambiar tu contraseña provisional una vez que ingreses.')
            ->line('Si tienes dudas, responde directamente a este correo.')
            ->salutation('El equipo de Taller Flow');
    }
}
