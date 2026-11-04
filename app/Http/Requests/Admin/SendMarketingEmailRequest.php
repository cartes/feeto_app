<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MarketingEmailTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMarketingEmailRequest extends FormRequest
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
            'tenant_ids' => ['required', 'array', 'min:1', 'max:200'],
            'tenant_ids.*' => ['integer', 'distinct', Rule::exists('tenants', 'id')],
            'template' => ['required', Rule::enum(MarketingEmailTemplate::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_ids.required' => 'Selecciona al menos un taller.',
            'tenant_ids.min' => 'Selecciona al menos un taller.',
            'tenant_ids.max' => 'Puedes enviar a un máximo de 200 talleres por vez.',
            'tenant_ids.*.exists' => 'Uno de los talleres seleccionados no existe.',
            'template.required' => 'Selecciona una plantilla.',
            'template.enum' => 'La plantilla seleccionada no es válida.',
        ];
    }
}
