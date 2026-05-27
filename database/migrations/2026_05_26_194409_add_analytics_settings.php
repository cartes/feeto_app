<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'key' => 'analytics_google_analytics_code',
                'group' => 'analytics',
                'description' => 'Código de Google Analytics (Script de seguimiento)',
                'is_secret' => false,
                'value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'analytics_google_search_console_code',
                'group' => 'analytics',
                'description' => 'Código de Verificación de Google Search Console (Meta Tag o HTML)',
                'is_secret' => false,
                'value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            if (! DB::table('settings')->where('key', $setting['key'])->exists()) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'analytics_google_analytics_code',
                'analytics_google_search_console_code',
            ])
            ->delete();
    }
};
