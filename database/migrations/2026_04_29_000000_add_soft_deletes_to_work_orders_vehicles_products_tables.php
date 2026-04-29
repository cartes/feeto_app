<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->softDeletes();
            // plate era único globalmente — incorrecto para multi-tenant y
            // además rompe soft deletes al intentar recrear una patente eliminada.
            // Se reemplaza por unique compuesto (tenant_id, plate).
            $table->dropUnique(['plate']);
            $table->unique(['tenant_id', 'plate'], 'uq_vehicles_tenant_id_plate');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropUnique('uq_vehicles_tenant_id_plate');
            $table->unique(['plate']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
