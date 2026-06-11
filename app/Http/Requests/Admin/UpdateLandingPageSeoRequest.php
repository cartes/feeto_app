<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

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
            'pages.*.key' => ['required', 'string', Rule::in(['home', 'pricing', 'trial', 'blog'])],
            'pages.*.title' => ['nullable', 'string', 'max:160'],
            'pages.*.description' => ['nullable', 'string', 'max:320'],
            'analytics_google_analytics_code' => ['nullable', 'string', 'max:10000'],
            'analytics_google_search_console_code' => ['nullable', 'string', 'max:10000'],
            'marketing_whatsapp_enabled' => ['required', 'boolean'],
            'marketing_whatsapp_number' => [
                Rule::requiredIf(fn (): bool => filter_var($this->input('marketing_whatsapp_enabled', false), FILTER_VALIDATE_BOOL)),
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9\s\-]{7,20}$/',
            ],
            'marketing_whatsapp_message' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pages.*.title.max' => 'El título SEO no debe superar los 160 caracteres.',
            'pages.*.description.max' => 'La descripción SEO no debe superar los 320 caracteres.',
            'marketing_whatsapp_number.required' => 'Debes ingresar un número de WhatsApp para activar el botón flotante.',
            'marketing_whatsapp_number.regex' => 'Ingresa un número de WhatsApp válido con código de país.',
            'marketing_whatsapp_message.max' => 'El mensaje inicial de WhatsApp no debe superar los 300 caracteres.',
        ];
    }
}
