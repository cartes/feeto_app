<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Country;
use App\Rules\LicensePlate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Multitenancy\Models\Tenant;

class PreviewReceptionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'patente' => strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', (string) $this->input('patente'))),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patente' => ['required', 'string', new LicensePlate(Tenant::current()?->country() ?? Country::Chile)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patente.required' => 'Debes ingresar una patente para consultar.',
        ];
    }
}
