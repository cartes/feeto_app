<?php

declare(strict_types=1);

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
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('work_order_id')->nullable()->change();
            $table->foreignId('client_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->string('uuid')->nullable()->unique()->after('vehicle_id');
            $table->text('notes')->nullable()->after('subtotal_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn(['client_id', 'vehicle_id', 'uuid', 'notes']);
            $table->foreignId('work_order_id')->nullable(false)->change();
        });
    }
};
