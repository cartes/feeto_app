<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un visitante (persona) por día y ámbito. Permite contar usuarios únicos reales,
 * páginas por visita, dispositivo y origen del tráfico.
 */
#[Fillable([
    'date',
    'visitor_hash',
    'scope',
    'tenant_id',
    'device',
    'referrer',
    'entry_path',
    'page_views',
    'first_seen_at',
    'last_seen_at',
])]
class PageVisitVisitor extends Model
{
    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'date',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
