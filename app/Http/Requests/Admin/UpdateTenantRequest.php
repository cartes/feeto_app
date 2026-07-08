<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($this->route('tenant'))],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', 'in:active,suspended'],
            'subscription_ends_at' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'seo_address' => ['nullable', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:100'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados para las reglas de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del taller es obligatorio.',
            'domain.required' => 'El dominio es obligatorio.',
            'domain.unique' => 'Este dominio ya está registrado.',
            'plan_id.required' => 'Debes seleccionar un plan.',
            'plan_id.exists' => 'El plan seleccionado no existe.',
            'status.required' => 'El estado del servicio es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'subscription_ends_at.date' => 'La fecha de término debe ser una fecha válida.',
            'phone.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'seo_address.max' => 'La dirección no puede tener más de 255 caracteres.',
            'comuna.max' => 'La comuna no puede tener más de 100 caracteres.',
            'whatsapp_number.regex' => 'El número de WhatsApp debe tener un formato válido.',
        ];
    }
}
