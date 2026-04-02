<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(): Response
    {
        $tenants = Tenant::latest()->get();

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
            'domain' => 'required|string|max:255|unique:tenants,domain,' . $tenant->id,
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
            'email' => 'required|email|max:255', // En producción podrías revisar uniqueness
            'password' => 'nullable|string|min:8',
        ]);

        $admin = $tenant->users()->first();

        if ($admin) {
            $admin->name = $validated['name'];
            $admin->email = $validated['email'];
            
            if (!empty($validated['password'])) {
                $admin->password = Hash::make($validated['password']);
            }
            
            $admin->save();
        } else {
            // Crea un nuevo administrador si no existe
            $admin = new User();
            $admin->name = $validated['name'];
            $admin->email = $validated['email'];
            $admin->password = Hash::make($validated['password'] ?? 'password');
            $admin->tenant_id = $tenant->id;
            $admin->save();
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
