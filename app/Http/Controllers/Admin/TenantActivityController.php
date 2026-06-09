<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\LoginLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TenantActivityController extends Controller
{
    public function show(Request $request, Tenant $tenant): Response
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Stats generales del tenant
        $stats = [
            'users_count' => User::where('tenant_id', $tenant->id)->count(),
            'clients_count' => Client::where('tenant_id', $tenant->id)->count(),
            'work_orders_total' => WorkOrder::where('tenant_id', $tenant->id)->count(),
            'work_orders_30d' => WorkOrder::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'quotes_total' => Quote::where('tenant_id', $tenant->id)->count(),
            'quotes_accepted' => Quote::where('tenant_id', $tenant->id)
                ->where('status', Quote::STATUS_ACCEPTED)->count(),
            'products_count' => Product::where('tenant_id', $tenant->id)->count(),
            'appointments_total' => Appointment::where('tenant_id', $tenant->id)->count(),
            'last_login_at' => LoginLog::where('tenant_id', $tenant->id)
                ->latest('created_at')->value('created_at'),
            'logins_30d' => LoginLog::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'revenue_total' => Payment::where('tenant_id', $tenant->id)
                ->where('status', 'approved')->sum('amount'),
        ];

        // Órdenes de trabajo por día (últimos 30 días)
        $workOrdersByDay = WorkOrder::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'total' => (int) $r->total]);

        // Distribución de estados de OT
        $workOrdersByStatus = WorkOrder::where('tenant_id', $tenant->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total]);

        // Logins por día (últimos 30 días)
        $loginsByDay = LoginLog::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'total' => (int) $r->total]);

        // Distribución de cotizaciones por estado
        $quotesByStatus = Quote::where('tenant_id', $tenant->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total]);

        // Pagos del tenant
        $payments = Payment::where('tenant_id', $tenant->id)
            ->latest('created_at')
            ->limit(20)
            ->get(['id', 'amount', 'currency', 'billing_period', 'status', 'paid_at', 'created_at']);

        // Usuarios del tenant
        $users = User::where('tenant_id', $tenant->id)
            ->select(['id', 'name', 'email', 'created_at'])
            ->addSelect([
                'login_count' => LoginLog::selectRaw('count(*)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenant->id),
                'last_login_at' => LoginLog::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenant->id)
                    ->latest()
                    ->limit(1),
            ])
            ->get();

        // Clientes del tenant (últimos 20)
        $clients = Client::where('tenant_id', $tenant->id)
            ->withCount('vehicles')
            ->latest()
            ->limit(20)
            ->get(['id', 'name', 'rut', 'phone', 'email', 'created_at']);

        // Órdenes de trabajo recientes
        $workOrders = WorkOrder::where('tenant_id', $tenant->id)
            ->with(['vehicle:id,plate'])
            ->latest()
            ->limit(20)
            ->get(['id', 'vehicle_id', 'status', 'total_amount', 'created_at']);

        // Cotizaciones recientes
        $quotes = Quote::where('tenant_id', $tenant->id)
            ->with(['client:id,name'])
            ->latest()
            ->limit(20)
            ->get(['id', 'client_id', 'status', 'subtotal_amount', 'created_at', 'sent_at']);

        // Inventario (productos con bajo stock primero)
        $products = Product::where('tenant_id', $tenant->id)
            ->orderByRaw('(physical_stock <= min_stock) DESC')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'type', 'sku', 'selling_price', 'physical_stock', 'min_stock']);

        // Citas recientes
        $appointments = Appointment::where('tenant_id', $tenant->id)
            ->latest('appointment_date')
            ->limit(20)
            ->get(['id', 'customer_name', 'phone', 'plate', 'appointment_date', 'status']);

        // Logins recientes
        $recentLogins = LoginLog::where('tenant_id', $tenant->id)
            ->with('user:id,name,email')
            ->latest()
            ->limit(30)
            ->get(['id', 'user_id', 'ip', 'created_at']);

        return Inertia::render('Admin/Tenants/Activity', [
            'tenant' => $tenant->only(['id', 'name', 'slug', 'domain', 'plan', 'status', 'is_active', 'subscription_ends_at', 'rut_taller']),
            'stats' => $stats,
            'charts' => [
                'work_orders_by_day' => $workOrdersByDay,
                'work_orders_by_status' => $workOrdersByStatus,
                'logins_by_day' => $loginsByDay,
                'quotes_by_status' => $quotesByStatus,
            ],
            'users' => $users,
            'clients' => $clients,
            'work_orders' => $workOrders,
            'quotes' => $quotes,
            'products' => $products,
            'appointments' => $appointments,
            'recent_logins' => $recentLogins,
            'payments' => $payments,
        ]);
    }
}
