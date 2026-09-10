<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmailTrackingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailTracking extends Model
{
    /** @use HasFactory<EmailTrackingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'recipient_email',
        'token',
        'subject',
        'sent_at',
        'opened_at',
        'open_count',
        'clicked_at',
        'click_count',
        'offer_discount_percent',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'offer_discount_percent' => 'integer',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $tracking): void {
            if (empty($tracking->token)) {
                $tracking->token = Str::random(40);
            }
            if (empty($tracking->sent_at)) {
                $tracking->sent_at = now();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpened(): bool
    {
        return $this->opened_at !== null;
    }

    public function isClicked(): bool
    {
        return $this->clicked_at !== null;
    }
}
