<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Country;
use App\Enums\TenantPlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(protected TenantSetupService $tenantSetupService) {}

    public function index(Request $request): Response
    {
        $search = $request->query('search', '');

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('rut_taller', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20, ['id', 'name', 'slug', 'rut_taller', 'is_active', 'status', 'plan', 'plan_id', 'max_users', 'subscription_ends_at'])
            ->withQueryString();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Tenants/Create');
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tenant = DB::transaction(function () use ($validated) {
            $slug = Tenant::generateUniqueSlug($validated['name']);
            $domain = $validated['domain'] ?? $this->generateUniqueDomain($slug);

            $country = Country::tryFrom((string) ($validated['country'] ?? 'CL')) ?? Country::Chile;
            $rutTaller = ! empty($validated['rut_taller'])
                ? $country->formatIdentification($validated['rut_taller'])
                : null;

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'domain' => $domain,
                'country' => $country,
                'rut_taller' => $rutTaller,
                'plan' => $validated['plan'],
                'is_active' => $validated['status'] === 'active',
                'status' => $validated['status'],
                'subscription_ends_at' => $validated['subscription_ends_at'] ? now()->parse($validated['subscription_ends_at']) : null,
            ]);

            $admin = new User;
            $admin->name = $validated['admin_name'];
            $admin->email = $validated['admin_email'];
            $admin->password = Hash::make($validated['admin_password']);
            $admin->tenant_id = $tenant->id;
            $admin->save();

            $this->tenantSetupService->provisionTenant($tenant, $admin);

            return $tenant;
        });

        return redirect()->route('admin.tenants.index')
            ->with('success', "Taller \"{$tenant->name}\" creado correctamente.");
    }

    private function generateUniqueDomain(string $slug): string
    {
        $base = $slug.'.tallerflow.cl';
        $domain = $base;
        $counter = 2;

        while (Tenant::where('domain', $domain)->exists()) {
            $domain = $slug.'-'.$counter.'.tallerflow.cl';
            $counter++;
        }

        return $domain;
    }

    public function edit(Tenant $tenant): Response
    {
        $tenant->load([
            'plan:id,name,slug,max_users',
            'users:id,tenant_id,name,email',
        ]);

        $plans = Plan::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'max_users', 'is_active']);

        $planId = $tenant->plan_id ?? $plans->firstWhere('slug', $tenant->currentPlan()->value)?->id;

        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'country' => $tenant->country?->value ?? 'CL',
                'rut_taller' => $tenant->rut_taller,
                'domain' => $tenant->domain,
                'plan' => $tenant->plan,
                'plan_id' => $planId,
                'status' => $tenant->status,
                'phone' => $tenant->phone,
                'seo_address' => $tenant->seo_address,
                'comuna' => $tenant->comuna,
                'whatsapp_number' => $tenant->whatsapp_number,
                'subscription_ends_at' => $tenant->subscription_ends_at?->toDateString(),
                'users' => $tenant->users
                    ->map(static fn (User $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ])
                    ->values()
                    ->all(),
            ],
            'plans' => $plans
                ->map(static fn (Plan $plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'max_users' => $plan->max_users,
                    'is_active' => (bool) $plan->is_active,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validated();
        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $resolvedPlan = TenantPlan::fromPlanModel($plan)?->value ?? $plan->slug;
        $country = Country::tryFrom((string) ($validated['country'] ?? 'CL')) ?? Country::Chile;
        $rutTaller = ! empty($validated['rut_taller'])
            ? $country->formatIdentification($validated['rut_taller'])
            : null;

        $tenant->update([
            'name' => $validated['name'],
            'country' => $country,
            'rut_taller' => $rutTaller,
            'domain' => $validated['domain'],
            'plan_id' => $plan->id,
            'plan' => $resolvedPlan,
            'plan_type' => $resolvedPlan,
            'max_users' => $plan->max_users,
            'status' => $validated['status'],
            'is_active' => $validated['status'] === 'active',
            'phone' => $validated['phone'],
            'seo_address' => $validated['seo_address'],
            'comuna' => $validated['comuna'],
            'whatsapp_number' => $validated['whatsapp_number'],
            'subscription_ends_at' => $validated['subscription_ends_at']
                ? now()->parse($validated['subscription_ends_at'])
                : null,
        ]);

        return back()->with('success', 'Taller actualizado correctamente.');
    }

    public function updateAdmin(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8',
        ]);

        $admin = $tenant->users()->first();
        $isNewAdmin = $admin === null;

        if ($admin) {
            $admin->name = $validated['name'];
            $admin->email = $validated['email'];

            if (! empty($validated['password'])) {
                $admin->password = Hash::make($validated['password']);
            }

            $admin->save();
        } else {
            $admin = new User;
            $admin->name = $validated['name'];
            $admin->email = $validated['email'];
            $admin->password = Hash::make($validated['password'] ?? 'password');
            $admin->tenant_id = $tenant->id;
            $admin->save();
        }

        if ($isNewAdmin) {
            $this->tenantSetupService->provisionTenant($tenant, $admin);
        }

        return back()->with('success', 'Administrador guardado correctamente.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $newStatus = $tenant->status === 'suspended' ? 'active' : 'suspended';

        $tenant->update(['status' => $newStatus]);

        return back()->with('success', "Estado del taller actualizado a {$newStatus}.");
    }
}
