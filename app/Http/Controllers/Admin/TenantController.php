<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantPlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(protected TenantSetupService $tenantSetupService) {}

    public function index(): Response
    {
        $tenants = Tenant::query()
            ->withCount('users')
            ->latest()
            ->get(['id', 'name', 'slug', 'rut_taller', 'is_active', 'status', 'plan', 'plan_id', 'max_users', 'subscription_ends_at']);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
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

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'domain' => $domain,
                'rut_taller' => $validated['rut_taller'],
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
        $tenant->load('users');

        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:tenants,domain,'.$tenant->id,
            'plan' => ['required', Rule::enum(TenantPlan::class)],
            'status' => 'required|in:active,suspended',
        ]);

        $tenant->update($validated);

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
