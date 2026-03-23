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
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->decimal('cost_price', 10, 2)->default(0)->after('description');
            $table->decimal('selling_price', 10, 2)->default(0)->after('cost_price');
            $table->integer('min_stock')->default(0)->after('reserved_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description', 'cost_price', 'selling_price', 'min_stock']);
        });
    }
};
