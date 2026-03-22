<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'plan_type')) {
                $table->string('plan_type')->default('starter')->after('is_active');
            }

            if (! Schema::hasColumn('tenants', 'max_users')) {
                $table->integer('max_users')->default(2)->after('plan_type');
            }

            // Rename whatsapp_token -> whatsapp_api_token if old column exists
            if (Schema::hasColumn('tenants', 'whatsapp_token') && ! Schema::hasColumn('tenants', 'whatsapp_api_token')) {
                $table->renameColumn('whatsapp_token', 'whatsapp_api_token');
            } elseif (! Schema::hasColumn('tenants', 'whatsapp_api_token')) {
                $table->text('whatsapp_api_token')->nullable()->after('billing_api_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'plan_type')) {
                $table->dropColumn('plan_type');
            }
            if (Schema::hasColumn('tenants', 'max_users')) {
                $table->dropColumn('max_users');
            }
            if (Schema::hasColumn('tenants', 'whatsapp_api_token')) {
                $table->renameColumn('whatsapp_api_token', 'whatsapp_token');
            }
        });
    }
};
