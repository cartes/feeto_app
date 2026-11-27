<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::table('trial_requests', function (Blueprint $table): void {
            $table->string('rut_taller', 30)->nullable()->unique()->after('business_name');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::table('trial_requests', function (Blueprint $table): void {
            $table->dropUnique(['rut_taller']);
            $table->dropColumn('rut_taller');
        });
    }
};
