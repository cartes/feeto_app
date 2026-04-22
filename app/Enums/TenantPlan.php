<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Plan;
use App\Services\PlanFeatureService;

enum TenantPlan: string
{
    case GRATUITO = 'gratuito';
    case BASICO = 'basico';
    case PROFESIONAL = 'profesional';
    case EMPRESA = 'empresa';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $plan): string => $plan->value,
            self::cases(),
        );
    }

    public static function fromStoredValue(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalized);

        return match ($normalized) {
            'gratuito', 'free', 'freemium', 'starter' => self::GRATUITO,
            'basico', 'basic' => self::BASICO,
            'profesional', 'professional', 'pro' => self::PROFESIONAL,
            'empresa', 'empresarial', 'enterprise', 'business' => self::EMPRESA,
            default => null,
        };
    }

    public static function fromPlanModel(?Plan $plan): ?self
    {
        if ($plan === null) {
            return null;
        }

        $resolvedFromIdentity = self::fromStoredValue($plan->slug)
            ?? self::fromStoredValue($plan->name);

        if ($resolvedFromIdentity !== null) {
            return $resolvedFromIdentity;
        }

        $featureKeys = array_map(
            static fn (string $feature): string => PlanFeatureService::normalizeFeatureKey($feature),
            $plan->feature_keys ?? [],
        );

        if (
            in_array(PlanFeatureService::FEATURE_AUTO_WHATSAPP, $featureKeys, true)
            || in_array(PlanFeatureService::FEATURE_COMMERCIAL_REPORTS, $featureKeys, true)
        ) {
            return self::EMPRESA;
        }

        if (
            in_array(PlanFeatureService::FEATURE_ADVANCED_INVENTORY, $featureKeys, true)
            || in_array(PlanFeatureService::FEATURE_SALES_MANAGEMENT, $featureKeys, true)
            || in_array(PlanFeatureService::FEATURE_COMMERCIAL_QUOTES, $featureKeys, true)
        ) {
            return self::PROFESIONAL;
        }

        if (
            in_array(PlanFeatureService::FEATURE_AI_RECEPTION, $featureKeys, true)
            || in_array(PlanFeatureService::FEATURE_CUSTOM_KANBAN, $featureKeys, true)
            || in_array(PlanFeatureService::FEATURE_CALENDAR_SCHEDULING, $featureKeys, true)
        ) {
            return self::BASICO;
        }

        return match ((int) $plan->max_users) {
            20 => self::EMPRESA,
            10 => self::PROFESIONAL,
            5 => self::BASICO,
            2 => self::GRATUITO,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::GRATUITO => 'Gratuito',
            self::BASICO => 'Básico',
            self::PROFESIONAL => 'Profesional',
            self::EMPRESA => 'Empresa',
        };
    }

    public function userLimit(): int
    {
        return match ($this) {
            self::GRATUITO => 2,
            self::BASICO => 5,
            self::PROFESIONAL => 10,
            self::EMPRESA => 20,
        };
    }

    public function maxBranches(): int
    {
        return match ($this) {
            self::GRATUITO => 1,
            self::BASICO => 2,
            self::PROFESIONAL => 5,
            self::EMPRESA => 0,
        };
    }

    /**
     * @return list<string>
     */
    public function featureKeys(): array
    {
        return match ($this) {
            self::GRATUITO => [],
            self::BASICO => [
                PlanFeatureService::FEATURE_AI_RECEPTION,
                PlanFeatureService::FEATURE_CUSTOM_KANBAN,
            ],
            self::PROFESIONAL => [
                PlanFeatureService::FEATURE_AI_RECEPTION,
                PlanFeatureService::FEATURE_CUSTOM_KANBAN,
                PlanFeatureService::FEATURE_CALENDAR_SCHEDULING,
                PlanFeatureService::FEATURE_ADVANCED_INVENTORY,
                PlanFeatureService::FEATURE_SALES_MANAGEMENT,
                PlanFeatureService::FEATURE_COMMERCIAL_QUOTES,
                PlanFeatureService::FEATURE_COMMERCIAL_REPORTS,
            ],
            self::EMPRESA => [
                PlanFeatureService::FEATURE_AI_RECEPTION,
                PlanFeatureService::FEATURE_CUSTOM_KANBAN,
                PlanFeatureService::FEATURE_CALENDAR_SCHEDULING,
                PlanFeatureService::FEATURE_ADVANCED_INVENTORY,
                PlanFeatureService::FEATURE_SALES_MANAGEMENT,
                PlanFeatureService::FEATURE_AUTO_WHATSAPP,
                PlanFeatureService::FEATURE_COMMERCIAL_QUOTES,
                PlanFeatureService::FEATURE_COMMERCIAL_REPORTS,
            ],
        };
    }

    public function includesFeature(string $feature): bool
    {
        return in_array(
            PlanFeatureService::normalizeFeatureKey($feature),
            $this->featureKeys(),
            true,
        );
    }
}
