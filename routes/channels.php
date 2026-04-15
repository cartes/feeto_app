<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('taller.{tenantId}', function ($user, $tenantId) {
    // Verificamos que el usuario pertenezca al taller (tenant) solicitado
    if (app()->bound('currentTenant')) {
        return (string) app('currentTenant')->id === (string) $tenantId;
    }

    return false;
});

Broadcast::channel('tenant.{tenantId}.work-orders', function ($user, $tenantId) {
    // Verificamos que el usuario pertenezca al taller (tenant) solicitado (usado en Kanban)
    if (app()->bound('currentTenant')) {
        return (string) app('currentTenant')->id === (string) $tenantId;
    }

    return false;
});

Broadcast::channel('tenant.{tenantId}.reception', function ($user, $tenantId) {
    // Verificamos que el usuario pertenezca al tenant para recibir datos de recepción
    if (app()->bound('currentTenant')) {
        return (string) app('currentTenant')->id === (string) $tenantId;
    }

    return false;
});
