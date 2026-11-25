<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendTenantDirectEmailRequest extends FormRequest
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
            'recipient_mode' => ['required', 'string', 'in:admin,user,all'],
            'user_id' => ['nullable', 'required_if:recipient_mode,user', 'integer'],
            'template_key' => ['nullable', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:5000'],
            'action_label' => ['nullable', 'string', 'max:80'],
            'action_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_mode.required' => 'Selecciona el destinatario del correo.',
            'recipient_mode.in' => 'El modo de destinatario seleccionado no es válido.',
            'user_id.required_if' => 'Debes seleccionar un usuario del taller.',
            'subject.required' => 'El asunto del correo es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 180 caracteres.',
            'body.required' => 'El mensaje del correo es obligatorio.',
            'body.max' => 'El mensaje no puede superar los 5000 caracteres.',
            'action_label.max' => 'El texto del botón no puede superar los 80 caracteres.',
            'action_url.url' => 'El enlace del botón debe ser una URL válida (incluyendo https://).',
            'action_url.max' => 'El enlace del botón no puede superar los 500 caracteres.',
        ];
    }
}
