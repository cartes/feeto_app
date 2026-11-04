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
            ->view('emails.trial-request-received', [
                'trialRequest' => $this->trialRequest,
                'bookingUrl' => $bookingUrl,
            ]);
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
