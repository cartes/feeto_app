<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;

class TenantSetupChecklistService
{
    public const STEPS = ['business_details', 'branding_contact', 'scheduling', 'public_page', 'share_link'];

    /** @return array<string, mixed> */
    public function forTenant(Tenant $tenant): array
    {
        $settingsParams = ['tenantBySlug' => $tenant->slug];
        $websiteUrl = route('taller.settings', [...$settingsParams, 'tab' => 'website']);
        $completedSteps = $tenant->setup_checklist ?? [];
        $steps = [
            ['id' => 'business_details', 'title' => 'Completar los datos del taller', 'description' => 'Revisa la dirección, teléfono y correo de tu sucursal principal.', 'url' => route('taller.settings', [...$settingsParams, 'tab' => 'branches']), 'action' => 'Revisar datos'],
            ['id' => 'branding_contact', 'title' => 'Revisar logo y contacto', 'description' => $tenant->hasFeature('seo_manager') ? 'Personaliza tu logo y comprueba los datos de contacto de tu página.' : 'Revisa tu logo. Los datos de contacto se administran en Sucursales.', 'url' => $websiteUrl, 'action' => 'Personalizar página'],
            ['id' => 'scheduling', 'title' => 'Configurar horarios de atención', 'description' => 'Revisa tus días de atención, horarios y bloqueos.', 'url' => route('taller.settings', [...$settingsParams, 'tab' => 'scheduling']), 'action' => 'Revisar horarios'],
            ['id' => 'public_page', 'title' => 'Revisar la página pública', 'description' => 'Abre tu página y comprueba cómo la verán tus clientes.', 'url' => route('taller.landing', $settingsParams), 'action' => 'Ver mi página'],
            ['id' => 'share_link', 'title' => 'Compartir el enlace', 'description' => 'Copia el enlace o compártelo por WhatsApp desde Mi página web. Marca este paso cuando lo hayas compartido.', 'url' => $websiteUrl, 'action' => 'Compartir mi página'],
        ];

        $steps = array_map(static fn (array $step): array => [
            ...$step,
            'external' => $step['id'] === 'public_page',
            'completed' => isset($completedSteps[$step['id']]),
        ], $steps);
        $completedCount = count(array_filter($steps, static fn (array $step): bool => $step['completed']));

        return [
            'steps' => $steps,
            'completed_count' => $completedCount,
            'total' => count($steps),
            'is_complete' => $completedCount === count($steps),
            'overview_url' => $websiteUrl,
            'update_url' => route('taller.settings.setup-checklist.update', $settingsParams),
        ];
    }

    public function updateStep(Tenant $tenant, string $step, bool $completed): void
    {
        $tenant->getConnection()->transaction(function () use ($tenant, $step, $completed): void {
            $lockedTenant = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $completedSteps = $lockedTenant->setup_checklist ?? [];

            if ($completed) {
                $completedSteps[$step] ??= now()->toIso8601String();
            } else {
                unset($completedSteps[$step]);
            }

            $lockedTenant->forceFill(['setup_checklist' => $completedSteps])->save();
            $tenant->setAttribute('setup_checklist', $completedSteps);
        });
    }
}
