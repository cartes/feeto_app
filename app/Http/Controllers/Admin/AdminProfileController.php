<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminPasswordRequest;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use App\Http\Requests\Admin\UpdateApiKeysRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminProfileController extends Controller
{
    public function edit(): Response
    {
        $aiSettings = Setting::getGroup('ai')->keyBy('key')->map(fn ($s) => [
            'value' => $s->is_secret ? ($s->value ? '••••••••' : null) : $s->value,
            'description' => $s->description,
            'is_secret' => $s->is_secret,
            'has_value' => ! empty($s->value),
        ]);

        $integrationSettings = Setting::getGroup('integrations')->keyBy('key')->map(fn ($s) => [
            'value' => $s->is_secret ? ($s->value ? '••••••••' : null) : $s->value,
            'description' => $s->description,
            'is_secret' => $s->is_secret,
            'has_value' => ! empty($s->value),
        ]);

        $paymentSettings = Setting::getGroup('payments')->keyBy('key')->map(fn ($s) => [
            'value' => $s->is_secret ? ($s->value ? '••••••••' : null) : $s->value,
            'description' => $s->description,
            'is_secret' => $s->is_secret,
            'has_value' => ! empty($s->value),
        ]);

        return Inertia::render('Admin/Profile', [
            'ai_settings' => $aiSettings,
            'integration_settings' => $integrationSettings,
            'payment_settings' => $paymentSettings,
        ]);
    }

    public function updateProfile(UpdateAdminProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $old = $user->only(['name', 'email']);

        $user->update($request->validated());

        AuditLog::record('profile.updated', 'Super-admin actualizó su perfil', $user, [
            'before' => $old,
            'after' => $user->only(['name', 'email']),
        ]);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(UpdateAdminPasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        AuditLog::record('password.changed', 'Super-admin cambió su contraseña');

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    public function updateApiKeys(UpdateApiKeysRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                Setting::set($key, $value);
            }
        }

        AuditLog::record('api_keys.updated', 'Super-admin actualizó las API keys del sistema');

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
