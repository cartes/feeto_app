<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reception_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('fuel_level')->nullable();
            $table->json('damages')->nullable();
            $table->json('belongings')->nullable();
            $table->text('notes')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signed_by_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_checklists');
    }
};
