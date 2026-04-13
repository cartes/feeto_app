<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Agrega columna slug única a la tabla tenants y rellena los tenants existentes.
     */
    public function up(): void
    {
        // Agregar columna nullable primero para no romper tenants existentes
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Rellenar slugs para tenants existentes generándolos desde el nombre
        DB::table('tenants')->orderBy('id')->each(function (object $tenant) {
            $base = Str::slug($tenant->name);
            $slug = $base;
            $counter = 2;

            while (DB::table('tenants')->where('slug', $slug)->where('id', '!=', $tenant->id)->exists()) {
                $slug = $base.'-'.$counter++;
            }

            DB::table('tenants')->where('id', $tenant->id)->update(['slug' => $slug]);
        });

        // Ahora lo hacemos not null
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
