<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\MarketingEmailTemplate;
use App\Models\Tenant;
use App\Services\MarketingWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TenantMarketingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly MarketingEmailTemplate $template,
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

        return (new MailMessage)
            ->subject($this->template->subject($this->tenant->name))
            ->replyTo($contactEmail)
            ->view($this->template->view(), [
                'tenant' => $this->tenant,
                'recipientName' => $notifiable->name,
                'loginUrl' => route('login'),
                'contactEmail' => $contactEmail,
                'unsubscribeUrl' => URL::signedRoute('marketing.unsubscribe', ['tenant' => $this->tenant->id]),
                'whatsappUrl' => app(MarketingWhatsAppService::class)->settings()['href'],
            ]);
    }
}
