<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendRenewalOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'custom_message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'discount_percent.integer' => 'El porcentaje de descuento debe ser un número entero.',
            'discount_percent.min' => 'El descuento no puede ser menor a 0%.',
            'discount_percent.max' => 'El descuento no puede ser mayor a 100%.',
            'custom_message.max' => 'El mensaje personalizado no puede exceder 500 caracteres.',
        ];
    }
}
