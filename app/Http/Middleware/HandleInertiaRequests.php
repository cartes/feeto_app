<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Permission\PermissionRegistrar;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $tenant = $this->resolveTenant($request, $user);
        $authorization = $user ? $this->resolveAuthorization($user, $tenant) : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'tenant_id' => $user->tenant_id,
                    'roles' => $authorization['roles'],
                    'permissions' => $authorization['permissions'],
                ]) : null,
            ],
            'tenant' => $tenant ? $tenant->only('id', 'name', 'slug') : null,
        ];
    }

    /**
     * @return array{roles: array<int, string>, permissions: array<int, string>}
     */
    private function resolveAuthorization(User $user, ?Tenant $tenant): array
    {
        if ($user->is_super_admin || ! $tenant) {
            return [
                'roles' => $user->getRoleNames()->values()->all(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            ];
        }

        $previousTenant = Tenant::current();
        $shouldRestoreTenant = ! $previousTenant || $previousTenant->id !== $tenant->id;

        if ($shouldRestoreTenant) {
            $tenant->makeCurrent();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        $authorization = [
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];

        if ($shouldRestoreTenant) {
            if ($previousTenant) {
                $previousTenant->makeCurrent();
            } else {
                Tenant::forgetCurrent();
            }
        }

        return $authorization;
    }

    private function resolveTenant(Request $request, ?User $user): ?Tenant
    {
        $tenant = Tenant::current();

        if ($tenant) {
            return $tenant;
        }

        $routeTenant = $request->route('tenantBySlug');

        if ($routeTenant instanceof Tenant) {
            return $routeTenant;
        }

        if (is_string($routeTenant) && $routeTenant !== '') {
            return Tenant::query()->where('slug', $routeTenant)->first();
        }

        if (! $user?->tenant_id) {
            return null;
        }

        return $user->relationLoaded('tenant')
            ? $user->tenant
            : $user->tenant()->first();
    }
}
