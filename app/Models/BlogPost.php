<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'summary', 'content', 'featured_image', 'featured_media_id', 'is_published', 'published_at'])]
class BlogPost extends Model
{
    /** @var array<string, string> */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 2;

        while (
            static::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'featured_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class);
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featuredMedia?->url ?? ($this->featured_image ?: null);
    }
}
