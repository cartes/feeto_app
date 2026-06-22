<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('tenant_id', $this->user()?->tenant_id))
                    ->ignore($productId),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')
                    ->where(fn ($q) => $q->where('tenant_id', $this->user()?->tenant_id)),
            ],
            'type' => ['nullable', 'string', 'in:repuesto_nacional,repuesto_internacional,insumo'],
            'description' => ['nullable', 'string'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'physical_stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'Ya existe un repuesto con este SKU.',
        ];
    }
}
