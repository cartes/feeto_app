<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

class TenantWelcomeService
{
    public function start(Tenant $tenant): void
    {
        $tenant->forceFill(['setup_checklist' => [
            ...($tenant->setup_checklist ?? []),
            'welcome' => ['required' => true, 'next_step' => 1],
        ]])->save();
    }

    public function shouldRedirect(Tenant $tenant): bool
    {
        $state = $tenant->setup_checklist['welcome'] ?? [];

        return ($state['required'] ?? false) && empty($state['deferred_at']) && empty($state['completed_at']);
    }

    /** @return array<string, mixed> */
    public function forTenant(Tenant $tenant): array
    {
        $state = $tenant->setup_checklist['welcome'] ?? [];
        $branch = $tenant->mainBranch();
        $profile = array_replace([
            'name' => $tenant->name,
            'comuna' => $tenant->comuna ?? '',
            'address' => $tenant->seo_address ?? $branch?->address ?? '',
            'phone' => $branch?->phone ?? $tenant->phone ?? '',
            'whatsapp_number' => $tenant->whatsapp_number ?? '',
            'email' => $branch?->email ?? '',
            'description' => $tenant->seo_description ?? '',
        ], $state['draft'] ?? []);
        $location = trim((string) $profile['comuna']);
        $suggestedDescription = trim((string) $profile['name']).' es un taller automotriz'
            .($location !== '' ? ' en '.$location : '').'. Consulta nuestros datos de contacto y solicita una hora desde nuestra página en TallerFlow.';

        return [
            'profile' => $profile,
            'suggested_description' => $suggestedDescription,
            'next_step' => (int) ($state['next_step'] ?? 1),
            'completed' => ! empty($state['completed_at']),
            'url' => route('taller.welcome.show', ['tenantBySlug' => $tenant->slug]),
            'update_url' => route('taller.welcome.update', ['tenantBySlug' => $tenant->slug]),
            'dashboard_url' => route('taller.dashboard', ['tenantBySlug' => $tenant->slug]),
            'public_url' => route('taller.landing', ['tenantBySlug' => $tenant->slug]),
        ];
    }

    /** @param array<string, mixed> $data */
    public function update(Tenant $tenant, array $data): void
    {
        $tenant->getConnection()->transaction(function () use ($tenant, $data): void {
            $lockedTenant = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $checklist = $lockedTenant->setup_checklist ?? [];
            $state = $checklist['welcome'] ?? ['next_step' => 1];

            if ($data['action'] === 'defer') {
                $state['deferred_at'] = now()->toIso8601String();
            } elseif ($data['action'] === 'finish') {
                if (! empty($state['completed_at'])) {
                    return;
                }
                if (($state['next_step'] ?? 1) < 4 || empty($state['draft']['description'])) {
                    throw ValidationException::withMessages(['step' => 'Completa los tres primeros pasos antes de finalizar.']);
                }
                $profile = $state['draft'];
                $branch = app(TenantSetupService::class)->ensureMainBranch($lockedTenant);
                $branch->update([
                    'address' => $profile['address'] ?? null,
                    'phone' => $profile['phone'],
                    'email' => $profile['email'] ?? null,
                ]);
                $lockedTenant->fill([
                    'name' => $profile['name'],
                    'comuna' => $profile['comuna'],
                    'seo_address' => $profile['address'] ?? null,
                    'phone' => $profile['phone'],
                    'whatsapp_number' => $profile['whatsapp_number'] ?? null,
                    'seo_description' => $profile['description'],
                ]);
                $state['completed_at'] = now()->toIso8601String();
                $checklist['business_details'] ??= $state['completed_at'];
                unset($state['draft']);
            } else {
                if (! empty($state['completed_at'])) {
                    throw ValidationException::withMessages(['step' => 'La bienvenida ya está completada. Puedes editar tus datos en Configuración.']);
                }
                $step = (int) $data['step'];
                if ($step > ($state['next_step'] ?? 1)) {
                    throw ValidationException::withMessages(['step' => 'Completa el paso anterior para continuar.']);
                }
                unset($data['action'], $data['step']);
                $state['draft'] = array_replace($state['draft'] ?? [], $data);
                $state['next_step'] = max($state['next_step'] ?? 1, $step + 1);
            }

            $checklist['welcome'] = $state;
            $lockedTenant->forceFill(['setup_checklist' => $checklist])->save();
            $tenant->setRawAttributes($lockedTenant->getAttributes(), true);
        });
    }
}
