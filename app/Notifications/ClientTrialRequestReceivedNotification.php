<?php

namespace App\Notifications;

use App\Models\TrialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientTrialRequestReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly TrialRequest $trialRequest)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Hemos recibido tu solicitud de prueba! - '.config('app.name'))
            ->greeting('¡Hola '.$this->trialRequest->name.'!')
            ->line('Gracias por tu interés en **'.config('app.name').'**.')
            ->line('Hemos recibido correctamente tu solicitud de prueba gratuita para tu negocio **'.$this->trialRequest->business_name.'**.')
            ->line('Nuestro equipo está revisando tus datos y te contactaremos a la brevedad para darte acceso a la plataforma.')
            ->line('Si tienes alguna duda, puedes responder a este correo.')
            ->salutation('¡Un saludo del equipo de '.config('app.name').'!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
