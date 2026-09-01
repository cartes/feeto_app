<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla unificada de validación de placas vehiculares.
 *
 * Acepta patentes con el formato local del país del taller y también
 * patentes extranjeras que calcen con el formato de otro país
 * latinoamericano soportado (ej: un vehículo argentino en un taller
 * chileno). El aviso de origen extranjero se resuelve aparte, con
 * Country::detectFromPlate().
 */
class LicensePlate implements ValidationRule
{
    public function __construct(private Country $country) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('La patente no tiene un formato válido.');

            return;
        }

        // Formato local del país del taller.
        if ($this->country->matchesPlate($value)) {
            return;
        }

        // Formato de otro país latinoamericano (patente extranjera).
        if (Country::detectFromPlate($value) !== []) {
            return;
        }

        $fail(sprintf(
            'La patente no coincide con el formato de %s ni con un formato internacional reconocido.',
            $this->country->label(),
        ));
    }
}
