<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantInactivityReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly Tenant $tenant) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = route('login');
        $days = (int) Setting::get('inactivity_days', config('services.inactivity.days', 10));

        return (new MailMessage)
            ->subject("[Taller Flow] ¡Te extrañamos! Vuelve a ingresar a tu taller {$this->tenant->name}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("Hemos notado que hace más de {$days} días no ingresas a la plataforma con tu taller **{$this->tenant->name}**.")
            ->line('Queremos recordarte que Taller Flow te ayuda a gestionar tus citas, órdenes de trabajo, cotizaciones e inventario de forma eficiente.')
            ->action('Ingresar a mi cuenta', $loginUrl)
            ->line('Si tienes alguna duda o necesitas ayuda para configurar tu taller, responde directamente a este correo.')
            ->salutation('El equipo de Taller Flow');
    }
}
