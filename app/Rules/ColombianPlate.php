<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida placas vehiculares colombianas en todos sus formatos oficiales.
 *
 * Formatos válidos:
 * - Autos particulares, públicos y clásicos: 3 letras + 3 números (ej: ABC123)
 * - Motocicletas modernas: 3 letras + 2 números + 1 letra (ej: ABC12D)
 * - Motocarros: 3 números + 3 letras (ej: 123ABC)
 * - Diplomáticas / Consulares / Oficiales: 2 letras + 4 números (ej: CD1234)
 * - Remolques / Maquinaria: 1 letra + 5 números (ej: R12345)
 * - Motos y formatos antiguos: 2 letras + 3 números (ej: AB123)
 */
class ColombianPlate implements ValidationRule
{
    public const PATTERN = '/^([A-Z]{3}\d{3}|[A-Z]{3}\d{2}[A-Z]|\d{3}[A-Z]{3}|[A-Z]{2}\d{4}|[A-Z]\d{5}|[A-Z]{2}\d{3})$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail('La placa no tiene un formato colombiano válido (ej: ABC123, ABC12D, CD1234, R12345).');
        }
    }
}
