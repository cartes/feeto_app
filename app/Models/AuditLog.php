<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'action', 'model_type', 'model_id', 'changes', 'ip', 'description'])]
class AuditLog extends Model
{
    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $description, ?Model $model = null, ?array $changes = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'changes' => $changes,
            'ip' => request()->ip(),
            'description' => $description,
        ]);
    }
}
