<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantCommercialSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'max_discount_without_approval' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'max_discount_without_approval.required' => 'Debes definir el descuento máximo sin aprobación.',
            'max_discount_without_approval.numeric' => 'El descuento máximo debe ser numérico.',
            'max_discount_without_approval.min' => 'El descuento máximo no puede ser negativo.',
            'max_discount_without_approval.max' => 'El descuento máximo no puede superar el 100%.',
        ];
    }
}
