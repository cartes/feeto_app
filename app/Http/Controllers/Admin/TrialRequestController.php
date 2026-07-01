<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TrialRequest;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TrialRequestController extends Controller
{
    public function __construct(protected TenantSetupService $tenantSetupService) {}

    public function index(Request $request): Response
    {
        $status = $request->get('status', 'pending');

        $query = TrialRequest::query()
            ->with(['approver:id,name', 'tenant:id,name,slug,subscription_ends_at'])
            ->latest();

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => TrialRequest::where('status', 'pending')->count(),
            'approved' => TrialRequest::where('status', 'approved')->count(),
            'rejected' => TrialRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/TrialRequests/Index', [
            'requests' => $requests,
            'counts' => $counts,
            'current_status' => $status,
        ]);
    }

    public function approve(Request $request, TrialRequest $trialRequest): RedirectResponse
    {
        if (! $trialRequest->isPending()) {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $validated = $request->validate([
            'admin_password' => 'required|string|min:8',
        ], [
            'admin_password.required' => 'La contraseña provisional del administrador es obligatoria.',
            'admin_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $slug = Tenant::generateUniqueSlug($trialRequest->business_name);
        $domain = $this->generateUniqueDomain($slug);

        $tenant = Tenant::create([
            'name' => $trialRequest->business_name,
            'slug' => $slug,
            'domain' => $domain,
            'plan' => 'basico',
            'is_active' => true,
            'status' => 'active',
            'subscription_ends_at' => now()->addDays(14),
        ]);

        $admin = new User;
        $admin->name = $trialRequest->name;
        $admin->email = $trialRequest->email;
        $admin->password = Hash::make($validated['admin_password']);
        $admin->tenant_id = $tenant->id;
        $admin->save();

        $this->tenantSetupService->provisionTenant($tenant, $admin);

        $trialRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'tenant_id' => $tenant->id,
        ]);

        return back()->with('success', "Solicitud aprobada. Taller \"{$tenant->name}\" creado con acceso trial de 14 días.");
    }

    public function destroy(TrialRequest $trialRequest): RedirectResponse
    {
        $tenant = $trialRequest->tenant_id ? $trialRequest->tenant : null;

        if ($tenant && $tenant->subscription_ends_at?->isFuture()) {
            return back()->with('error', 'No se puede eliminar un taller con prueba activa.');
        }

        $tenantName = $tenant?->name;
        $tenant?->delete();

        $trialRequest->delete();

        $message = $tenantName
            ? "Eliminado: el taller \"{$tenantName}\" y su usuario han sido borrados definitivamente. El correo queda libre para una nueva solicitud."
            : 'Solicitud eliminada definitivamente.';

        return back()->with('success', $message);
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

    public function reject(Request $request, TrialRequest $trialRequest): RedirectResponse
    {
        if (! $trialRequest->isPending()) {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $trialRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return back()->with('success', 'Solicitud rechazada correctamente.');
    }
}
