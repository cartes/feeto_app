<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['rut']);
            $table->unique(['tenant_id', 'rut'], 'uq_clients_tenant_id_rut');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('uq_clients_tenant_id_rut');
            $table->unique(['rut']);
        });
    }
};
