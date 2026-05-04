<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'patente' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patente.required' => 'Debes ingresar una patente para consultar.',
            'patente.size' => 'La patente debe tener 6 caracteres.',
            'patente.regex' => 'La patente solo puede contener letras y números.',
        ];
    }
}
