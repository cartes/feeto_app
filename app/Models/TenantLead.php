<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Database\Factories\TenantLeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantLead extends Model
{
    public const SOURCE_WHATSAPP = 'whatsapp';

    public const SOURCE_CONTACT_GENERAL = 'contact_general';

    public const SOURCE_CONTACT_QUOTE = 'contact_quote';

    /** @use HasFactory<TenantLeadFactory> */
    use HasFactory, TenantAware;

    protected $fillable = [
        'tenant_id',
        'source',
        'channel',
        'visitor_name',
        'email',
        'phone',
        'metadata',
        'occurred_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
