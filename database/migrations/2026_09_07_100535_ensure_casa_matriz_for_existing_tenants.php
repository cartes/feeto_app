<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            // Verificar si el taller ya tiene una sucursal principal
            $mainBranch = DB::table('branches')
                ->where('tenant_id', $tenant->id)
                ->where('is_main', true)
                ->first();

            if (! $mainBranch) {
                $anyBranch = DB::table('branches')
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('id')
                    ->first();

                if ($anyBranch) {
                    DB::table('branches')
                        ->where('id', $anyBranch->id)
                        ->update(['is_main' => true]);
                    $branchId = $anyBranch->id;
                } else {
                    $branchId = DB::table('branches')->insertGetId([
                        'tenant_id' => $tenant->id,
                        'name' => 'Casa Matriz',
                        'code' => 'MATRIZ',
                        'address' => $tenant->seo_address ?? $tenant->comuna ?? null,
                        'phone' => $tenant->whatsapp_number ?? null,
                        'is_active' => true,
                        'is_main' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $branchId = $mainBranch->id;
            }

            // Asociar registros huérfanos a la Casa Matriz del taller
            DB::table('work_orders')
                ->where('tenant_id', $tenant->id)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);

            DB::table('appointments')
                ->where('tenant_id', $tenant->id)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);

            DB::table('products')
                ->where('tenant_id', $tenant->id)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);

            DB::table('services')
                ->where('tenant_id', $tenant->id)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
