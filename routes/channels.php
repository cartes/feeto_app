<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('taller.{tenantId}', function ($user, $tenantId) {
    // Verificamos que el usuario pertenezca al taller (tenant) solicitado
    return (int) $user->tenant_id === (int) $tenantId;
});
