<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['filename', 'original_name', 'path', 'mime_type', 'size', 'width', 'height', 'alt_text', 'description'])]
class MediaFile extends Model
{
    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return '/storage/' . $this->path;
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'featured_media_id');
    }
}
