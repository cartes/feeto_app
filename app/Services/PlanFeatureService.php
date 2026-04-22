<?php

declare(strict_types=1);

namespace App\Services;

class PlanFeatureService
{
    public const FEATURE_COMMERCIAL_QUOTES = 'commercial_quotes_enabled';

    public const FEATURE_COMMERCIAL_REPORTS = 'commercial_reports_enabled';

    /**
     * @return array<string, array{label: string, description: string, min_plan: string}>
     */
    public function definitions(): array
    {
        return [
            self::FEATURE_COMMERCIAL_QUOTES => [
                'label' => 'Cotizaciones comerciales y aprobación del cliente',
                'description' => 'Catálogo de servicios, cotización en OT y aprobación/rechazo por el cliente.',
                'min_plan' => 'Profesional',
            ],
            self::FEATURE_COMMERCIAL_REPORTS => [
                'label' => 'Reportes comerciales y notificaciones avanzadas',
                'description' => 'Historial comercial, panel de notificaciones y reportes generales por cliente.',
                'min_plan' => 'Empresarial',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function allFeatureKeys(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * @return array{label: string, description: string, min_plan: string}
     */
    public function definition(string $feature): array
    {
        return $this->definitions()[$feature] ?? [
            'label' => $feature,
            'description' => '',
            'min_plan' => 'superior',
        ];
    }

    public function upgradeMessage(string $feature): string
    {
        $definition = $this->definition($feature);

        return "Disponible desde el plan {$definition['min_plan']}.";
    }
}
