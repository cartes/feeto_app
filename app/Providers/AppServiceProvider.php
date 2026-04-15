<?php

namespace App\Providers;

use App\Listeners\RecordLoginLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Observers\OrderItemObserver;
use App\Observers\OrderObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
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
        Vite::prefetch(concurrency: 3);

        Order::observe(OrderObserver::class);
        OrderItem::observe(OrderItemObserver::class);

        Event::listen(Login::class, RecordLoginLog::class);

        Route::bind('tenantBySlug', fn (string $value) => Tenant::where('slug', $value)->firstOrFail());
    }
}
