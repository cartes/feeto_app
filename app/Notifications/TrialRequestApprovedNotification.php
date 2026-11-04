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
        $loginUrl = route('login');

        return (new MailMessage)
            ->subject("[Taller Flow] ¡Tu prueba gratuita de {$this->tenant->name} fue aprobada!")
            ->view('emails.trial-request-approved', [
                'trialRequest' => $this->trialRequest,
                'tenant' => $this->tenant,
                'temporaryPassword' => $this->temporaryPassword,
                'trialEndsAt' => $trialEndsAt,
                'bookingUrl' => $bookingUrl,
                'loginUrl' => $loginUrl,
            ]);
    }
}
