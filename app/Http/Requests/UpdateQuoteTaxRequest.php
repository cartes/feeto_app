<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteTaxRequest extends FormRequest
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
            'apply_tax' => ['required', 'boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'apply_tax.required' => 'Debes indicar si se aplica impuesto.',
            'apply_tax.boolean' => 'El campo de aplicación de impuesto no es válido.',
            'tax_rate.numeric' => 'La tasa de impuesto debe ser un valor numérico.',
            'tax_rate.min' => 'La tasa de impuesto no puede ser negativa.',
            'tax_rate.max' => 'La tasa de impuesto no puede superar el 100%.',
        ];
    }
}
