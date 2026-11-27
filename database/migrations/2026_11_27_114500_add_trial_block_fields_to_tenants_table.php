<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para soportar el bloqueo de talleres con prueba agotada.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('status', 30)->default('active')->change();
            $table->string('blocked_email')->nullable()->after('status');
            $table->timestamp('blocked_at')->nullable()->after('blocked_email');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['blocked_email', 'blocked_at']);
        });
    }
};
