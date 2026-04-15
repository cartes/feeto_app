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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->text('admin_notes')->nullable()->after('status');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['admin_notes', 'plan_id']);
        });
    }
};
