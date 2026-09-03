<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\MarketingSeoPages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLandingPageSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pages' => ['required', 'array'],
            'pages.*.key' => ['required', 'string', Rule::in(MarketingSeoPages::keys())],
            'pages.*.title' => ['nullable', 'string', 'max:160'],
            'pages.*.description' => ['nullable', 'string', 'max:320'],
            'analytics_google_analytics_code' => ['nullable', 'string', 'max:10000'],
            'analytics_google_search_console_code' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pages.*.title.max' => 'El título SEO no debe superar los 160 caracteres.',
            'pages.*.description.max' => 'La descripción SEO no debe superar los 320 caracteres.',
        ];
    }
}
