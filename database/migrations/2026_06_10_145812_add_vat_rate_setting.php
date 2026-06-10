<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the current configured VAT rate (defaults to 0.19)
        $currentVatRate = (string) config('billing.vat_rate', '0.19');

        Setting::updateOrCreate(
            ['key' => 'vat_rate'],
            [
                'group' => 'payments',
                'description' => 'Tasa de IVA general aplicada a facturación y comisiones',
                'is_secret' => false,
                'value' => $currentVatRate,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'vat_rate')->delete();
    }
};
