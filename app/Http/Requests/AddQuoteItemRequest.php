<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddQuoteItemRequest extends FormRequest
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
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasProduct = $this->filled('product_id');
            $hasService = $this->filled('service_id');
            $hasDescription = filled($this->input('description'));

            if ((int) $hasProduct + (int) $hasService > 1) {
                $validator->errors()->add('product_id', 'Selecciona solo un origen para el ítem.');
            }

            if (! $hasProduct && ! $hasService && ! $hasDescription) {
                $validator->errors()->add('description', 'Debes ingresar una descripción o seleccionar un repuesto o servicio.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'discount_percent.numeric' => 'El descuento debe ser numérico.',
            'discount_percent.min' => 'El descuento no puede ser negativo.',
            'discount_percent.max' => 'El descuento no puede superar el 100%.',
        ];
    }
}
