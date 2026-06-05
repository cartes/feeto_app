<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'path', 'date', 'visits', 'unique_visits'])]
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

    public static function record(string $path, ?int $tenantId = null, bool $isUnique = false): void
    {
        try {
            $visit = static::firstOrCreate(
                ['tenant_id' => $tenantId, 'path' => $path, 'date' => today()],
                ['visits' => 0, 'unique_visits' => 0]
            );
        } catch (\Throwable) {
            $visit = static::where([
                'tenant_id' => $tenantId,
                'path' => $path,
                'date' => today(),
            ])->first();
        }

        if ($visit) {
            $visit->increment('visits');
            if ($isUnique) {
                $visit->increment('unique_visits');
            }
        }
    }
}
