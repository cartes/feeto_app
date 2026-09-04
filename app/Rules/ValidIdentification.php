<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidIdentification implements ValidationRule
{
    public function __construct(private ?Country $country = null)
    {
        $this->country ??= Country::Chile;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if (! $this->country->validateIdentification($value)) {
            $docName = $this->country->identificationName();
            $example = $this->country->identificationPlaceholder();

            $fail("El {$docName} ingresado no es válido (ejemplo: {$example}).");
        }
    }
}
