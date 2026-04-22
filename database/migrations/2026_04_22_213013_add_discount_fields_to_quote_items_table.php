<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            $table->decimal('original_unit_price', 12, 2)
                ->nullable()
                ->after('quantity');
            $table->decimal('discount_percent', 5, 2)
                ->default(0)
                ->after('original_unit_price');
            $table->decimal('discount_amount', 12, 2)
                ->default(0)
                ->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            $table->dropColumn([
                'original_unit_price',
                'discount_percent',
                'discount_amount',
            ]);
        });
    }
};
