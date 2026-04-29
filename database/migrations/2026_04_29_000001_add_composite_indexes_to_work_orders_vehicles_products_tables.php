<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------------------
        // work_orders
        // -------------------------------------------------------------------
        // (tenant_id, status)     → Kanban: WHERE tenant_id = ? GROUP BY status
        // (tenant_id, updated_at) → Dashboard: WHERE tenant_id = ? ORDER BY updated_at DESC LIMIT 5
        // (tenant_id, vehicle_id) → relación vehicle->workOrders() con global scope de tenant activo
        // Los FK individuales (vehicle_id, branch_id) los crea Laravel/InnoDB y se mantienen
        // para satisfacer la constraint; estos compuestos los complementan.
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status'],     'idx_work_orders_tenant_status');
            $table->index(['tenant_id', 'updated_at'], 'idx_work_orders_tenant_updated_at');
            $table->index(['tenant_id', 'vehicle_id'], 'idx_work_orders_tenant_vehicle_id');
        });

        // -------------------------------------------------------------------
        // vehicles
        // -------------------------------------------------------------------
        // (tenant_id, client_id) → relación client->vehicles() con global scope de tenant activo
        // La búsqueda por patente ya está cubierta por uq_vehicles_tenant_id_plate (migración anterior).
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->index(['tenant_id', 'client_id'], 'idx_vehicles_tenant_client_id');
        });

        // -------------------------------------------------------------------
        // products
        // -------------------------------------------------------------------
        // (tenant_id, name)           → WorkOrderController::show: SELECT … ORDER BY name
        // (tenant_id, created_at)     → InventoryController::index: latest()->paginate(20)
        // (tenant_id, physical_stock) → ProductController API: WHERE physical_stock > 0
        //                               + escaneo de eventos StockDepleted / SafetyStockReached
        // (tenant_id, type)           → InventoryController::show: WHERE type = ? LIMIT 5
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['tenant_id', 'name'],           'idx_products_tenant_name');
            $table->index(['tenant_id', 'created_at'],     'idx_products_tenant_created_at');
            $table->index(['tenant_id', 'physical_stock'], 'idx_products_tenant_physical_stock');
            $table->index(['tenant_id', 'type'],           'idx_products_tenant_type');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropIndex('idx_work_orders_tenant_status');
            $table->dropIndex('idx_work_orders_tenant_updated_at');
            $table->dropIndex('idx_work_orders_tenant_vehicle_id');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropIndex('idx_vehicles_tenant_client_id');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('idx_products_tenant_name');
            $table->dropIndex('idx_products_tenant_created_at');
            $table->dropIndex('idx_products_tenant_physical_stock');
            $table->dropIndex('idx_products_tenant_type');
        });
    }
};
