<?php

declare(strict_types=1);

namespace App\Enums;

use App\Rules\ChileanPlate;
use App\Rules\ColombianPlate;

/**
 * Países soportados por la plataforma.
 *
 * Los países "operacionales" (donde pueden registrarse talleres) son un
 * subconjunto; el resto existe para reconocer patentes extranjeras que
 * lleguen a un taller (ej: un vehículo argentino en un taller chileno).
 */
enum Country: string
{
    case Chile = 'CL';
    case Colombia = 'CO';
    case Argentina = 'AR';
    case Bolivia = 'BO';
    case Brazil = 'BR';
    case Ecuador = 'EC';
    case Mexico = 'MX';
    case Paraguay = 'PY';
    case Peru = 'PE';
    case Uruguay = 'UY';

    /**
     * Países donde la plataforma opera comercialmente (registro de talleres).
     *
     * @return list<self>
     */
    public static function operational(): array
    {
        return [self::Chile, self::Colombia];
    }

    /**
     * Nombre legible del país.
     */
    public function label(): string
    {
        return match ($this) {
            self::Chile => 'Chile',
            self::Colombia => 'Colombia',
            self::Argentina => 'Argentina',
            self::Bolivia => 'Bolivia',
            self::Brazil => 'Brasil',
            self::Ecuador => 'Ecuador',
            self::Mexico => 'México',
            self::Paraguay => 'Paraguay',
            self::Peru => 'Perú',
            self::Uruguay => 'Uruguay',
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
            self::Argentina => '🇦🇷',
            self::Bolivia => '🇧🇴',
            self::Brazil => '🇧🇷',
            self::Ecuador => '🇪🇨',
            self::Mexico => '🇲🇽',
            self::Paraguay => '🇵🇾',
            self::Peru => '🇵🇪',
            self::Uruguay => '🇺🇾',
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
            self::Argentina => '+54',
            self::Bolivia => '+591',
            self::Brazil => '+55',
            self::Ecuador => '+593',
            self::Mexico => '+52',
            self::Paraguay => '+595',
            self::Peru => '+51',
            self::Uruguay => '+598',
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
            self::Argentina => '+54 9 11 1234 5678',
            self::Bolivia => '+591 7123 4567',
            self::Brazil => '+55 11 91234 5678',
            self::Ecuador => '+593 99 123 4567',
            self::Mexico => '+52 55 1234 5678',
            self::Paraguay => '+595 981 123 456',
            self::Peru => '+51 912 345 678',
            self::Uruguay => '+598 91 234 567',
        };
    }

    /**
     * Código ISO 3166-1 alpha-2 (alias del value).
     */
    public function isoCode(): string
    {
        return $this->value;
    }

    /**
     * Formatos de patente característicos del país (sin anclas ni separadores,
     * sobre patentes normalizadas: mayúsculas y solo alfanuméricos).
     *
     * Usados para *detectar* el país de origen de una patente, por lo que son
     * más estrictos que los patrones de validación local (que aceptan además
     * placas especiales o provisorias).
     *
     * @return list<string>
     */
    public function platePatterns(): array
    {
        return match ($this) {
            // LLLL·NN (nuevo, sin vocales ni MNÑQ), LL·NNNN (antiguo),
            // motos LLL·NN (nueva) / LL·NNN (antigua)
            self::Chile => ['[BCDFGHJKLPRSTVWXYZ]{4}\d{2}', '[A-Z]{2}\d{4}', '[BCDFGHJKLPRSTVWXYZ]{3}\d{2}', '[A-Z]{2}\d{3}'],
            // ABC123, ABC12D, 123ABC, CD1234, R12345, AB123
            self::Colombia => ['[A-Z]{3}\d{3}', '[A-Z]{3}\d{2}[A-Z]', '\d{3}[A-Z]{3}', '[A-Z]{2}\d{4}', '[A-Z]\d{5}', '[A-Z]{2}\d{3}'],
            // Mercosur AB123CD, antiguo ABC123, motos A123BCD / 123ABC
            self::Argentina => ['[A-Z]{2}\d{3}[A-Z]{2}', '[A-Z]{3}\d{3}', '[A-Z]\d{3}[A-Z]{3}', '\d{3}[A-Z]{3}'],
            // 1234ABC, 123ABC
            self::Bolivia => ['\d{4}[A-Z]{3}', '\d{3}[A-Z]{3}'],
            // Mercosur ABC1D23, antiguo ABC1234
            self::Brazil => ['[A-Z]{3}\d[A-Z]\d{2}', '[A-Z]{3}\d{4}'],
            // ABC1234, ABC123
            self::Ecuador => ['[A-Z]{3}\d{4}', '[A-Z]{3}\d{3}'],
            // ABC1234, ABC123D
            self::Mexico => ['[A-Z]{3}\d{4}', '[A-Z]{3}\d{3}[A-Z]'],
            // Mercosur AAAA123, motos 123AAAA, antiguo ABC123
            self::Paraguay => ['[A-Z]{4}\d{3}', '\d{3}[A-Z]{4}', '[A-Z]{3}\d{3}'],
            // ABC123 / A1B234 (2° y 3° pueden ser dígito), motos 1234AB / AB1234
            self::Peru => ['[A-Z][A-Z0-9]{2}\d{3}', '\d{4}[A-Z]{2}', '[A-Z]{2}\d{4}'],
            // ABC1234
            self::Uruguay => ['[A-Z]{3}\d{4}'],
        };
    }

    /**
     * Patrón de validación para patentes locales del país. Más permisivo que
     * los patrones de detección (acepta placas especiales/provisorias).
     */
    public function plateValidationPattern(): string
    {
        return match ($this) {
            self::Chile => ChileanPlate::PATTERN,
            self::Colombia => ColombianPlate::PATTERN,
            default => '/^('.implode('|', $this->platePatterns()).')$/',
        };
    }

    /**
     * ¿La patente calza con el formato local del país?
     */
    public function matchesPlate(string $plate): bool
    {
        return preg_match($this->plateValidationPattern(), $plate) === 1;
    }

    /**
     * Detecta a qué países podría pertenecer una patente normalizada
     * (mayúsculas, sin guiones ni espacios).
     *
     * @return list<self> Países cuyos formatos calzan con la patente.
     */
    public static function detectFromPlate(string $plate): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $country): bool => preg_match(
                '/^('.implode('|', $country->platePatterns()).')$/',
                $plate
            ) === 1,
        ));
    }
}
