<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('taller.{tenantId}', function ($user, $tenantId) {
    return $user->is_super_admin || (string) $user->tenant_id === (string) $tenantId;
});

Broadcast::channel('tenant.{tenantId}.work-orders', function ($user, $tenantId) {
    return $user->is_super_admin || (string) $user->tenant_id === (string) $tenantId;
});

Broadcast::channel('tenant.{tenantId}.reception', function ($user, $tenantId) {
    return $user->is_super_admin || (string) $user->tenant_id === (string) $tenantId;
});
