<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key' => 'marketing_whatsapp_enabled',
                'group' => 'marketing',
                'description' => 'Activa el botón flotante global de WhatsApp en las páginas públicas',
                'is_secret' => false,
                'value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'marketing_whatsapp_number',
                'group' => 'marketing',
                'description' => 'Número de WhatsApp del super-admin para leads orgánicos',
                'is_secret' => false,
                'value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'marketing_whatsapp_message',
                'group' => 'marketing',
                'description' => 'Mensaje inicial del botón flotante global de WhatsApp',
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

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'marketing_whatsapp_enabled',
                'marketing_whatsapp_number',
                'marketing_whatsapp_message',
            ])
            ->delete();
    }
};
