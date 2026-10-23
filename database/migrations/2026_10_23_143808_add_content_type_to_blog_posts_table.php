<?php

declare(strict_types=1);

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->string('content_type')->default('article')->index();
        });

        BlogPost::query()
            ->has('categories', '=', 1)
            ->whereHas('categories', fn ($query) => $query->where('slug', 'tutoriales'))
            ->update(['content_type' => 'tutorial']);
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropIndex(['content_type']);
            $table->dropColumn('content_type');
        });
    }
};
