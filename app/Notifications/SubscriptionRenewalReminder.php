<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SubscriptionRenewalReminder extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly ?string $trackingToken = null,
        public readonly ?int $discountPercent = null,
        public readonly ?string $customMessage = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysLeft = (int) max(0, now()->diffInDays($this->tenant->subscription_ends_at, false));
        $expiryDate = $this->tenant->subscription_ends_at?->format('d/m/Y') ?? 'próximamente';

        $actionUrl = $this->trackingToken
            ? route('mail.click', ['token' => $this->trackingToken])
            : route('checkout.show', $this->tenant->slug);

        $hasDiscount = $this->discountPercent !== null && $this->discountPercent > 0;

        $subject = $hasDiscount
            ? "[Taller Flow] ¡Oferta especial! Renueva con {$this->discountPercent}% de descuento en {$this->tenant->name}"
            : "[Taller Flow] Tu suscripción vence en {$daysLeft} día(s) — Renueva ahora";

        $actionLabel = $hasDiscount
            ? "Aprovechar {$this->discountPercent}% de descuento y renovar"
            : 'Renovar suscripción';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hola, {$notifiable->name}")
            ->line("La suscripción de tu taller **{$this->tenant->name}** vence el **{$expiryDate}**.");

        if ($hasDiscount) {
            $mail->line("¡Queremos que sigas operando sin interrupciones! Por eso, tienes disponible un beneficio exclusivo de **{$this->discountPercent}% de descuento** al renovar tu plan hoy.");
        } else {
            $mail->line('Para mantener el acceso ininterrumpido a todas tus funciones, renueva tu plan antes de esa fecha.');
        }

        if (! empty($this->customMessage)) {
            $mail->line($this->customMessage);
        }

        $mail->action($actionLabel, $actionUrl)
            ->line('Si ya realizaste el pago, puedes ignorar este correo.')
            ->salutation('El equipo de Taller Flow');

        if ($this->trackingToken) {
            $trackingUrl = route('mail.track', ['token' => $this->trackingToken]);
            $mail->line(new HtmlString(
                '<img src="'.$trackingUrl.'" width="1" height="1" border="0" style="display:none;width:1px!important;height:1px!important;max-height:0;overflow:hidden;mso-hide:all;" alt="" />'
            ));
        }

        return $mail;
    }
}
