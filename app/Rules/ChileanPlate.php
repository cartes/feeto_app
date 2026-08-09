<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ChileanPlate implements ValidationRule
{
    /**
     * Autos: 6 caracteres alfanuméricos (LLLL·NN nuevo, LL·NNNN antiguo,
     * más placas especiales/provisorias).
     * Motos: 5 caracteres (LLL·NN nuevo, LL·NNN antiguo).
     */
    public const PATTERN = '/^([A-Z0-9]{6}|[A-Z]{3}[0-9]{2}|[A-Z]{2}[0-9]{3})$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail('La patente no tiene un formato válido (auto: 6 caracteres, moto: 5).');
        }
    }
}
