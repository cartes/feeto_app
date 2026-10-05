<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;

class TenantDailyOverviewService
{
    public function __construct(private BranchContext $branchContext) {}

    /** @return array<string, mixed> */
    public function forTenant(Tenant $tenant, User $user, int $todayAppointmentCount): array
    {
        $branchId = $this->branchContext->id();
        $routeParams = ['tenantBySlug' => $tenant->slug];
        $summary = [];
        $pendingAppointments = collect();

        if ($user->can('appointments.manage')) {
            $pendingQuery = Appointment::query()
                ->where('tenant_id', $tenant->id)
                ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
                ->where('status', 'pending')
                ->where('appointment_date', '>=', now()->startOfDay());

            $summary[] = ['id' => 'today', 'label' => 'Citas de hoy', 'count' => $todayAppointmentCount, 'url' => route('appointments.index', $routeParams), 'attention' => false];
            $pendingCount = (clone $pendingQuery)->count();
            $summary[] = ['id' => 'pending', 'label' => 'Citas por revisar', 'count' => $pendingCount, 'url' => '#pending-appointments', 'attention' => true];
            $pendingAppointments = $pendingCount > 0 ? $pendingQuery->with(['client', 'vehicle'])->orderBy('appointment_date')->orderBy('id')->limit(5)->get() : collect();
        }

        if ($user->can('work-orders.view') || $user->can('work-orders.view-own')) {
            $orders = WorkOrder::query()
                ->where('tenant_id', $tenant->id)
                ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId));
            $counts = $orders->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
            $readyCount = (int) $counts->get(WorkOrder::STATUS_LISTO, 0);
            $summary[] = ['id' => 'in_progress', 'label' => 'Órdenes en proceso', 'count' => $counts->sum() - $readyCount, 'url' => route('work-orders.index', $routeParams), 'attention' => false];
            $summary[] = ['id' => 'ready', 'label' => 'Órdenes listas', 'count' => $readyCount, 'url' => route('work-orders.index', [...$routeParams, 'view' => 'list', 'status' => WorkOrder::STATUS_LISTO]), 'attention' => true];
        }

        return ['summary' => $summary, 'pending_appointments' => $pendingAppointments];
    }
}
