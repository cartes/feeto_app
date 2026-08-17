<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla unificada de validación de placas vehiculares.
 * Delega la validación al validador específico del país.
 */
class LicensePlate implements ValidationRule
{
    public function __construct(private Country $country) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rule = match ($this->country) {
            Country::Chile => new ChileanPlate,
            Country::Colombia => new ColombianPlate,
        };

        $rule->validate($attribute, $value, $fail);
    }
}
