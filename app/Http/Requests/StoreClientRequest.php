<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'max_credit_limit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Debes ingresar el nombre del cliente.',
            'rut.required' => 'Debes ingresar el RUT del cliente.',
            'email.email' => 'El correo del cliente no es válido.',
            'max_credit_limit.numeric' => 'El límite de crédito debe ser numérico.',
            'max_credit_limit.min' => 'El límite de crédito no puede ser negativo.',
        ];
    }
}
