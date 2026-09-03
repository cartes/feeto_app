<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tenant;
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
        $tenantId = Tenant::current()?->id ?? $this->user()?->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')
                    ->where(fn ($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q)
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
