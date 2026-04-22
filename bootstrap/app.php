<?php

use App\Http\Middleware\EnsureTenantFeatureEnabled;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RecordPageVisit;
use App\Http\Middleware\SetTenantRouteDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            RecordPageVisit::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'tenant.feature' => EnsureTenantFeatureEnabled::class,
        ]);

        $middleware->prependToPriorityList(RoleMiddleware::class, NeedsTenant::class);
        $middleware->appendToPriorityList(NeedsTenant::class, SetTenantRouteDefaults::class);
        $middleware->prependToPriorityList(PermissionMiddleware::class, SetTenantRouteDefaults::class);
        $middleware->prependToPriorityList(RoleOrPermissionMiddleware::class, SetTenantRouteDefaults::class);
    })
    ->withEvents()
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
