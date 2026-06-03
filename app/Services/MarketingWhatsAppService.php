<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class MarketingWhatsAppService
{
    /**
     * @return array{
     *     is_enabled: bool,
     *     is_ready: bool,
     *     number: string,
     *     sanitized_number: string,
     *     message: string,
     *     href: string|null
     * }
     */
    public function settings(): array
    {
        $isEnabled = filter_var(Setting::get('marketing_whatsapp_enabled', false), FILTER_VALIDATE_BOOL);
        $number = (string) (Setting::get('marketing_whatsapp_number', '') ?? '');
        $message = (string) (Setting::get('marketing_whatsapp_message', 'Hola, vi TallerFlow y quiero más información.') ?? '');
        $sanitizedNumber = preg_replace('/\D+/', '', $number) ?? '';
        $isReady = $isEnabled && filled($sanitizedNumber);

        return [
            'is_enabled' => $isEnabled,
            'is_ready' => $isReady,
            'number' => $number,
            'sanitized_number' => $sanitizedNumber,
            'message' => $message,
            'href' => $isReady
                ? sprintf('https://wa.me/%s?text=%s', $sanitizedNumber, rawurlencode($message))
                : null,
        ];
    }
}
