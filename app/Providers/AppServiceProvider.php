<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Observers\OrderItemObserver;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Order::observe(OrderObserver::class);
        OrderItem::observe(OrderItemObserver::class);

        /** Binding de ruta para resolver un Tenant por su slug. */
        Route::bind('tenantBySlug', fn (string $value) => Tenant::where('slug', $value)->firstOrFail());
    }
}
