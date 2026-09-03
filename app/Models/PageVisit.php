<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'scope', 'path', 'date', 'visits', 'unique_visits'])]
class PageVisit extends Model
{
    /** Páginas públicas de marketing (home, precios, blog, trial, login...). */
    public const SCOPE_SITE = 'site';

    /** Páginas públicas de un taller (landing /taller/{slug}, checkout, cotización pública). */
    public const SCOPE_TENANT = 'tenant';

    /** Uso interno de la aplicación por usuarios autenticados. */
    public const SCOPE_APP = 'app';

    /** @var list<string> */
    public const SCOPES = [self::SCOPE_SITE, self::SCOPE_TENANT, self::SCOPE_APP];

    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function record(string $path, ?int $tenantId = null, bool $isUnique = false, string $scope = self::SCOPE_SITE): void
    {
        try {
            $visit = static::firstOrCreate(
                ['tenant_id' => $tenantId, 'path' => $path, 'date' => today()],
                ['visits' => 0, 'unique_visits' => 0, 'scope' => $scope]
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
