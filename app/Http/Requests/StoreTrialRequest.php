<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Country;
use App\Models\Tenant;
use App\Models\TrialRequest;
use App\Rules\ValidIdentification;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el RUT/identificación del taller según el país seleccionado antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $rawRut = $this->input('rut_taller');

        if (! is_string($rawRut) || trim($rawRut) === '') {
            return;
        }

        $country = Country::tryFrom((string) $this->input('country')) ?? Country::Chile;
        $formatted = $country->formatIdentification($rawRut);

        if ($formatted !== '') {
            $this->merge([
                'rut_taller' => $formatted,
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $country = Country::tryFrom((string) $this->input('country')) ?? Country::Chile;

        return [
            'country' => ['required', 'string', Rule::in(array_column(Country::operational(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:filter', 'max:255', 'unique:trial_requests,email'],
            'phone' => ['required', 'string', 'max:30'],
            'business_name' => ['required', 'string', 'max:255'],
            'rut_taller' => [
                'bail',
                'required',
                'string',
                'max:20',
                new ValidIdentification($country),
                'unique:tenants,rut_taller',
                'unique:trial_requests,rut_taller',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $normalized = preg_replace('/[^0-9A-Z]/', '', strtoupper($value)) ?? '';
                    if ($normalized === '') {
                        return;
                    }

                    $existsInTenants = Tenant::query()
                        ->whereNotNull('rut_taller')
                        ->whereRaw("REPLACE(REPLACE(REPLACE(UPPER(rut_taller), '.', ''), '-', ''), ' ', '') = ?", [$normalized])
                        ->exists();

                    $existsInTrialRequests = TrialRequest::query()
                        ->whereNotNull('rut_taller')
                        ->whereRaw("REPLACE(REPLACE(REPLACE(UPPER(rut_taller), '.', ''), '-', ''), ' ', '') = ?", [$normalized])
                        ->exists();

                    if ($existsInTenants || $existsInTrialRequests) {
                        $fail('Este RUT de taller ya está registrado.');
                    }
                },
            ],
            'business_type' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'users_estimate' => ['nullable', 'integer', 'min:1', 'max:999'],
            'requested_plan' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'country.required' => 'Debes seleccionar un país.',
            'country.in' => 'El país seleccionado no es válido.',
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe una solicitud con este correo electrónico.',
            'phone.required' => 'El teléfono es obligatorio.',
            'business_name.required' => 'El nombre del negocio es obligatorio.',
            'rut_taller.required' => 'El RUT del taller es obligatorio.',
            'rut_taller.max' => 'El RUT del taller no puede tener más de 20 caracteres.',
            'rut_taller.unique' => 'Este RUT de taller ya está registrado.',
            'business_type.required' => 'El rubro del negocio es obligatorio.',
            'terms.accepted' => 'Debes aceptar los términos para continuar.',
        ];
    }
}
