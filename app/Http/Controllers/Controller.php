<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;

abstract class Controller
{
    /**
     * @return array{title: string, description: string, og_image: string}
     */
    protected function resolveMarketingSeo(string $pageKey, string $defaultTitle, string $defaultDescription): array
    {
        return [
            'title' => Setting::get("seo_{$pageKey}_title", $defaultTitle),
            'description' => Setting::get("seo_{$pageKey}_description", $defaultDescription),
            'og_image' => $this->resolveSocialImageUrl(Setting::get("seo_{$pageKey}_og_image", '')),
        ];
    }

    protected function resolveSocialImageUrl(?string $configuredUrl = null): string
    {
        if (filled($configuredUrl)) {
            return $configuredUrl;
        }

        return url('/images/tallerflow-social-share.png');
    }
}
