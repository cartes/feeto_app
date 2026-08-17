<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'business_name',
        'business_type',
        'city',
        'country',
        'users_estimate',
        'requested_plan',
        'message',
        'status',
        'approved_at',
        'approved_by',
        'tenant_id',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'users_estimate' => 'integer',
        'country' => Country::class,
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
