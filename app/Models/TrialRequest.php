<?php

declare(strict_types=1);

namespace App\Models;

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
