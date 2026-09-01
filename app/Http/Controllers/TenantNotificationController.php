<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantNotification;
use Illuminate\Http\JsonResponse;

class TenantNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $tenant = Tenant::current();

        $notifications = TenantNotification::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'type', 'title', 'body', 'data', 'read_at', 'created_at']);

        $unreadCount = TenantNotification::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(TenantNotification $notification): JsonResponse
    {
        $tenant = Tenant::current();

        if ($notification->tenant_id !== $tenant->id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        $tenant = Tenant::current();

        TenantNotification::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
