<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use App\Services\MarketingWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantDirectEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $emailSubject,
        public readonly string $emailBody,
        public readonly ?string $trackingToken = null,
        public readonly ?string $actionLabel = null,
        public readonly ?string $actionUrl = null,
        public readonly ?string $senderName = null,
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
        $contactEmail = (string) config('mail.from.address');
        $trackingPixelUrl = $this->trackingToken
            ? route('mail.track', ['token' => $this->trackingToken])
            : null;

        $trackedActionUrl = null;
        if (! empty($this->actionUrl)) {
            $trackedActionUrl = $this->trackingToken
                ? route('mail.click', ['token' => $this->trackingToken])
                : $this->actionUrl;
        }

        return (new MailMessage)
            ->subject($this->emailSubject)
            ->replyTo($contactEmail)
            ->view('emails.tenant-direct-communication', [
                'tenant' => $this->tenant,
                'recipientName' => $notifiable->name ?? $this->tenant->name,
                'recipientEmail' => $notifiable->email ?? null,
                'subject' => $this->emailSubject,
                'body' => $this->emailBody,
                'actionLabel' => ! empty($this->actionLabel) ? $this->actionLabel : 'Ir a Taller Flow',
                'actionUrl' => $trackedActionUrl,
                'senderName' => $this->senderName ?? 'Equipo Taller Flow',
                'contactEmail' => $contactEmail,
                'trackingPixelUrl' => $trackingPixelUrl,
                'whatsappUrl' => app(MarketingWhatsAppService::class)->settings()['href'] ?? null,
            ]);
    }
}
