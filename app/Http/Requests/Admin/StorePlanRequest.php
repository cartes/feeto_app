<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'integer', 'min:0'],
            'price_annual' => ['required', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:200'],
            'feature_keys' => ['nullable', 'array'],
            'feature_keys.*' => ['string', 'max:100'],
            'max_users' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'trial_days' => ['integer', 'min:0', 'max:90'],
            'discount_percent' => ['integer', 'min:0', 'max:100'],
            'discount_valid_until' => ['nullable', 'date', 'after:today'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
