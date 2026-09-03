<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('apply_tax')->default(true)->after('subtotal_amount');
            $table->decimal('tax_rate', 5, 2)->default(0.00)->after('apply_tax');
            $table->decimal('tax_amount', 12, 2)->default(0.00)->after('tax_rate');
            $table->decimal('total_amount', 12, 2)->default(0.00)->after('tax_amount');
        });

        // Inicializar total_amount igual a subtotal_amount para cotizaciones existentes
        DB::table('quotes')->update([
            'total_amount' => DB::raw('subtotal_amount'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['apply_tax', 'tax_rate', 'tax_amount', 'total_amount']);
        });
    }
};
