<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->text('seo_description')->nullable()->after('admin_notes');
            $table->string('seo_address')->nullable()->after('seo_description');
            $table->string('whatsapp_number')->nullable()->after('seo_address');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['seo_description', 'seo_address', 'whatsapp_number']);
        });
    }
};
