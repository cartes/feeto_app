<?php

declare(strict_types=1);

namespace App\Enums;

enum MarketingEmailTemplate: string
{
    case Tutorials = 'tutorials';
    case CustomTool = 'custom_tool';

    public function label(): string
    {
        return match ($this) {
            self::Tutorials => 'Guía paso a paso y tutoriales (con link de agendamiento)',
            self::CustomTool => 'Contáctanos: creamos una herramienta a tu medida',
        };
    }

    public function subject(string $tenantName): string
    {
        return match ($this) {
            self::Tutorials => "[Taller Flow] {$tenantName}: guía rápida y tutorial para aprovechar tu cuenta",
            self::CustomTool => "[Taller Flow] {$tenantName}: ¿qué herramienta necesita tu taller?",
        };
    }

    public function view(): string
    {
        return match ($this) {
            self::Tutorials => 'emails.marketing-tutorials',
            self::CustomTool => 'emails.marketing-custom-tool',
        };
    }

    public function trackingType(): string
    {
        return 'marketing_'.$this->value;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $template): array => ['value' => $template->value, 'label' => $template->label()],
            self::cases(),
        );
    }
}
