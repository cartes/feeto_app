<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Países soportados por la plataforma.
 * Escalable a más países en el futuro.
 */
enum Country: string
{
    case Chile = 'CL';
    case Colombia = 'CO';

    /**
     * Nombre legible del país.
     */
    public function label(): string
    {
        return match ($this) {
            self::Chile => 'Chile',
            self::Colombia => 'Colombia',
        };
    }

    /**
     * Emoji de bandera para UI.
     */
    public function flag(): string
    {
        return match ($this) {
            self::Chile => '🇨🇱',
            self::Colombia => '🇨🇴',
        };
    }

    /**
     * Prefijo telefónico internacional.
     */
    public function phonePrefix(): string
    {
        return match ($this) {
            self::Chile => '+56',
            self::Colombia => '+57',
        };
    }

    /**
     * Placeholder de teléfono para formularios.
     */
    public function phonePlaceholder(): string
    {
        return match ($this) {
            self::Chile => '+56 9 1234 5678',
            self::Colombia => '+57 300 123 4567',
        };
    }

    /**
     * Código ISO 3166-1 alpha-2 (alias del value).
     */
    public function isoCode(): string
    {
        return $this->value;
    }
}
