<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('productCategory')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $this->user()?->tenant_id))
                    ->ignore($id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una categoría con este nombre.',
        ];
    }
}
