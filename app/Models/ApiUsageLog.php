<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'service', 'date', 'calls_count'])]
class ApiUsageLog extends Model
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

    public static function record(string $service, ?int $tenantId = null): void
    {
        static::upsert(
            [['tenant_id' => $tenantId, 'service' => $service, 'date' => now()->toDateString(), 'calls_count' => 1]],
            uniqueBy: ['tenant_id', 'service', 'date'],
            update: ['calls_count' => \DB::raw('calls_count + 1')],
        );
    }
}
