<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visit_visitors', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('visitor_hash', 40);
            $table->string('scope', 16)->default('site');
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device', 12)->default('desktop');
            $table->string('referrer', 120)->nullable();
            $table->string('entry_path')->default('/');
            $table->unsignedInteger('page_views')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->unique(['date', 'visitor_hash', 'scope']);
            $table->index(['scope', 'date']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visit_visitors');
    }
};
