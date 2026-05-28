<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('blog_posts', 'featured_media_id')) {
                $table->foreignId('featured_media_id')->nullable()->after('featured_image')
                    ->constrained('media_files')->nullOnDelete();
            } else {
                $table->foreign('featured_media_id')->references('id')->on('media_files')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropForeign(['featured_media_id']);
            $table->dropColumn('featured_media_id');
        });
    }
};
