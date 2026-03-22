<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del taller (ej: Medelauto)
            $table->string('domain')->unique(); // ej: medelauto.tuapp.cl
            $table->string('database')->nullable(); // Si decides usar BD separadas después

            // Campos específicos para Chile
            $table->string('rut_taller')->unique(); // RUT Empresa
            $table->text('billing_api_key')->nullable(); // Token del proveedor DTE (Encriptado)
            $table->text('whatsapp_token')->nullable(); // Token para notificaciones de WhatsApp (Encriptado)

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
