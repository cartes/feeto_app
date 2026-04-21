<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'plan' => 'nullable|string|max:50',
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
