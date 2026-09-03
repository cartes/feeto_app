<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_visits', function (Blueprint $table): void {
            $table->string('scope', 16)->default('site')->after('tenant_id')->index();
        });

        // Backfill histórico: lo que tenía tenant era, en su mayoría, uso interno de la app.
        DB::table('page_visits')
            ->whereNotNull('tenant_id')
            ->update(['scope' => 'app']);

        // Landing pública del taller: exactamente "taller/{slug}" (sin más segmentos).
        DB::table('page_visits')
            ->whereNotNull('tenant_id')
            ->where('path', 'like', 'taller/%')
            ->where('path', 'not like', 'taller/%/%')
            ->update(['scope' => 'tenant']);

        // Checkout público y cotizaciones públicas también son tráfico público del taller.
        DB::table('page_visits')
            ->whereNotNull('tenant_id')
            ->where(function ($query): void {
                $query->where('path', 'like', 'checkout/%')
                    ->orWhere('path', 'like', 'cotizacion/%');
            })
            ->update(['scope' => 'tenant']);
    }

    public function down(): void
    {
        Schema::table('page_visits', function (Blueprint $table): void {
            $table->dropIndex(['scope']);
            $table->dropColumn('scope');
        });
    }
};
