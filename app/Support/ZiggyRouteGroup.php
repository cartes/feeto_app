<?php

namespace App\Support;

use Illuminate\Http\Request;

class ZiggyRouteGroup
{
    /**
     * Grupo de config/ziggy.php que corresponde a la petición (null = todas las rutas).
     */
    public static function forRequest(Request $request): ?string
    {
        if ($request->user()?->is_super_admin) {
            return null;
        }

        if ($request->routeIs('admin.*')) {
            return 'admin';
        }

        if ($request->routeIs('taller.dashboard', 'taller.welcome.*', 'work-orders.*', 'quotes.*', 'clients.*', 'appointments.*', 'inventory.*', 'product-categories.*', 'services.*', 'invoices.*', 'reports.*', 'taller.settings*', 'taller.roles.*', 'taller.users.*', 'tenant.users.*', 'notifications.*', 'receptions.*', 'subscription.*', 'branches.*', 'profile.*', 'api.*')) {
            return 'tenant';
        }

        return 'public';
    }
}
