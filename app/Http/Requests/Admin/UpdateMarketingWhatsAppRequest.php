<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarketingWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
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
            'marketing_whatsapp_number.required' => 'Debes ingresar un número de WhatsApp para activar el botón flotante.',
            'marketing_whatsapp_number.regex' => 'Ingresa un número de WhatsApp válido con código de país.',
            'marketing_whatsapp_message.max' => 'El mensaje inicial de WhatsApp no debe superar los 300 caracteres.',
        ];
    }
}
