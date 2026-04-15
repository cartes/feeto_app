<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Login;

class RecordLoginLog
{
    public function handle(Login $event): void
    {
        LoginLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'tenant_id' => $event->user->tenant_id ?? null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
