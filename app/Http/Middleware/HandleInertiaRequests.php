<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanFeatureService;
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
            'flash' => fn (): array => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'status' => $request->session()->get('status'),
                'booking_success' => $request->session()->get('booking_success'),
            ],
            'tenant' => $tenant ? $tenant->only('id', 'name', 'slug') : null,
            'planAccess' => $this->resolvePlanAccess($tenant),
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

    /**
     * @return array<string, mixed>|null
     */
    private function resolvePlanAccess(?Tenant $tenant): ?array
    {
        if (! $tenant) {
            return null;
        }

        $featureService = app(PlanFeatureService::class);
        $definitions = $featureService->definitions();
        $planModel = $tenant->plan()->first();

        return [
            'plan_name' => $planModel?->name ?? $tenant->plan ?? $tenant->plan_type,
            'feature_keys' => $planModel?->feature_keys ?? [],
            'commercial_quotes_enabled' => $tenant->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES),
            'commercial_reports_enabled' => $tenant->hasFeature(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS),
            'upgrade_messages' => collect($definitions)->mapWithKeys(
                fn (array $definition, string $key): array => [$key => $featureService->upgradeMessage($key)]
            )->all(),
            'definitions' => $definitions,
        ];
    }
}
