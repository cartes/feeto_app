<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Tenant;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TenantController extends Controller
{
    public function index(): Response
    {
        $tenants = Tenant::withCount('users')->latest()->get();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
        ]);
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $newStatus = $tenant->status === 'suspended' ? 'active' : 'suspended';
        
        $tenant->update(['status' => $newStatus]);

        return back()->with('success', "Estado del taller actualizado a {$newStatus}.");
    }
}
