<?php

namespace App\Providers;

use App\Listeners\RecordLoginLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Observers\OrderItemObserver;
use App\Observers\OrderObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootstrapRuntimeConfiguration();

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        Order::observe(OrderObserver::class);
        OrderItem::observe(OrderItemObserver::class);
        User::observe(UserObserver::class);

        Event::listen(Login::class, RecordLoginLog::class);

        Route::bind('tenantBySlug', fn (string $value) => Tenant::where('slug', $value)->firstOrFail());
    }

    private function bootstrapRuntimeConfiguration(): void
    {
        try {
            $runtimeConfig = [];

            $aiProvider = Setting::get('ai_provider');
            if (filled($aiProvider)) {
                $runtimeConfig['ai.default'] = $aiProvider;
            }

            $aiImageProvider = Setting::get('ai_image_provider');
            if (filled($aiImageProvider)) {
                $runtimeConfig['ai.default_for_images'] = $aiImageProvider;
            }

            $providerKeys = [
                'anthropic' => Setting::get('anthropic_api_key'),
                'gemini' => Setting::get('gemini_api_key'),
                'openai' => Setting::get('openai_api_key'),
            ];

            foreach ($providerKeys as $provider => $key) {
                if (filled($key)) {
                    $runtimeConfig["ai.providers.{$provider}.key"] = $key;
                }
            }

            $boostrApiKey = Setting::get('boostr_api_key');
            if (filled($boostrApiKey)) {
                $runtimeConfig['services.boostr.key'] = $boostrApiKey;
            }

            $boostrBaseUrl = Setting::get('boostr_base_url');
            if (filled($boostrBaseUrl)) {
                $runtimeConfig['services.boostr.base_url'] = $boostrBaseUrl;
            }

            $vatRate = Setting::get('vat_rate');
            if (filled($vatRate)) {
                $runtimeConfig['billing.vat_rate'] = (float) $vatRate;
            }

            if ($runtimeConfig !== []) {
                Config::set($runtimeConfig);
            }
        } catch (\Throwable) {
        }
    }
}
