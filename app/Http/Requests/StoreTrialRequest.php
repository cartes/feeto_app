<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Country;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country' => ['required', 'string', Rule::in(array_column(Country::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:filter', 'max:255', 'unique:trial_requests,email'],
            'phone' => ['required', 'string', 'max:30'],
            'business_name' => ['required', 'string', 'max:255'],
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
            'business_type.required' => 'El rubro del negocio es obligatorio.',
            'terms.accepted' => 'Debes aceptar los términos para continuar.',
        ];
    }
}
