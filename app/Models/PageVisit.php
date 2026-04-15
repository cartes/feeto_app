<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'path', 'date', 'visits'])]
class PageVisit extends Model
{
    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function record(string $path, ?int $tenantId = null): void
    {
        static::upsert(
            [['tenant_id' => $tenantId, 'path' => $path, 'date' => now()->toDateString(), 'visits' => 1]],
            uniqueBy: ['tenant_id', 'path', 'date'],
            update: ['visits' => \DB::raw('visits + 1')],
        );
    }
}
