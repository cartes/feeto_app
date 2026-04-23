<?php

declare(strict_types=1);

namespace App\Services;

class PlanFeatureService
{
    public const FEATURE_AI_RECEPTION = 'ai_reception';

    public const FEATURE_CUSTOM_KANBAN = 'custom_kanban';

    public const FEATURE_CALENDAR_SCHEDULING = 'calendar_scheduling';

    public const FEATURE_ADVANCED_INVENTORY = 'advanced_inventory';

    public const FEATURE_SALES_MANAGEMENT = 'sales_management';

    public const FEATURE_AUTO_WHATSAPP = 'auto_whatsapp';

    public const FEATURE_COMMERCIAL_QUOTES = 'commercial_quotes_enabled';

    public const FEATURE_COMMERCIAL_REPORTS = 'commercial_reports_enabled';

    public const FEATURE_CUSTOM_ROLES = 'custom_roles';

    /**
     * @return array<string, array{label: string, description: string, min_plan: string}>
     */
    public function definitions(): array
    {
        return [
            self::FEATURE_AI_RECEPTION => [
                'label' => 'Recepción asistida por IA',
                'description' => 'Habilita flujos de recepción inteligente y ayudas automáticas para la toma inicial.',
                'min_plan' => 'Básico',
            ],
            self::FEATURE_CUSTOM_KANBAN => [
                'label' => 'Kanban personalizado',
                'description' => 'Permite adaptar la operación del taller con vistas Kanban por plan.',
                'min_plan' => 'Básico',
            ],
            self::FEATURE_CALENDAR_SCHEDULING => [
                'label' => 'Agenda en calendario',
                'description' => 'Habilita gestión de agenda y programación de citas desde el calendario.',
                'min_plan' => 'Profesional',
            ],
            self::FEATURE_ADVANCED_INVENTORY => [
                'label' => 'Inventario avanzado',
                'description' => 'Desbloquea capacidades avanzadas de control de stock y gestión de repuestos.',
                'min_plan' => 'Profesional',
            ],
            self::FEATURE_SALES_MANAGEMENT => [
                'label' => 'Gestión de ventas',
                'description' => 'Activa ventas, servicios, cotizaciones y flujos comerciales del taller.',
                'min_plan' => 'Profesional',
            ],
            self::FEATURE_AUTO_WHATSAPP => [
                'label' => 'WhatsApp automático',
                'description' => 'Envía automatizaciones y notificaciones por WhatsApp desde el sistema.',
                'min_plan' => 'Empresarial',
            ],
            self::FEATURE_CUSTOM_ROLES => [
                'label' => 'Roles personalizados',
                'description' => 'Crea roles con permisos granulares completamente personalizados para tu equipo.',
                'min_plan' => 'Empresa',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, min_plan: string}>
     */
    private function legacyDefinitions(): array
    {
        return [
            self::FEATURE_COMMERCIAL_QUOTES => [
                'label' => 'Cotizaciones comerciales',
                'description' => 'Compatibilidad con el gating legacy de servicios y cotizaciones.',
                'min_plan' => 'Profesional',
            ],
            self::FEATURE_COMMERCIAL_REPORTS => [
                'label' => 'Reportes comerciales',
                'description' => 'Compatibilidad con el gating legacy de reportes avanzados.',
                'min_plan' => 'Profesional',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function allFeatureKeys(): array
    {
        return array_keys([
            ...$this->definitions(),
            ...$this->legacyDefinitions(),
        ]);
    }

    /**
     * @return list<string>
     */
    public function frontendFeatureKeys(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * @return array{label: string, description: string, min_plan: string}
     */
    public function definition(string $feature): array
    {
        $normalizedFeature = self::normalizeFeatureKey($feature);

        return $this->definitions()[$normalizedFeature]
            ?? $this->legacyDefinitions()[$normalizedFeature]
            ?? [
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

    public static function normalizeFeatureKey(string $feature): string
    {
        return match ($feature) {
            'ocr_enabled', 'smart_reception_enabled' => self::FEATURE_AI_RECEPTION,
            'appointments_enabled' => self::FEATURE_CALENDAR_SCHEDULING,
            'inventory_enabled' => self::FEATURE_ADVANCED_INVENTORY,
            'whatsapp_enabled' => self::FEATURE_AUTO_WHATSAPP,
            default => $feature,
        };
    }

    /**
     * @return list<string>
     */
    public static function possibleFeatureKeys(string $feature): array
    {
        $normalizedFeature = self::normalizeFeatureKey($feature);

        $keys = [$normalizedFeature];

        if ($normalizedFeature === self::FEATURE_AI_RECEPTION) {
            $keys[] = 'ocr_enabled';
            $keys[] = 'smart_reception_enabled';
        }

        if ($normalizedFeature === self::FEATURE_CALENDAR_SCHEDULING) {
            $keys[] = 'appointments_enabled';
        }

        if ($normalizedFeature === self::FEATURE_ADVANCED_INVENTORY) {
            $keys[] = 'inventory_enabled';
        }

        if ($normalizedFeature === self::FEATURE_AUTO_WHATSAPP) {
            $keys[] = 'whatsapp_enabled';
        }

        return array_values(array_unique($keys));
    }
}
