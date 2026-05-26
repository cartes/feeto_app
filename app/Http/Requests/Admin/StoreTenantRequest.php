<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TenantPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
            'rut_taller' => ['nullable', 'string', 'max:20', 'unique:tenants,rut_taller'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'plan' => ['required', Rule::enum(TenantPlan::class)],
            'status' => ['required', 'in:active,suspended'],
            'subscription_ends_at' => ['nullable', 'date'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
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
            'name.string' => 'El nombre del taller debe ser una cadena de texto.',
            'name.max' => 'El nombre del taller no puede tener más de 255 caracteres.',
            'rut_taller.max' => 'El RUT del taller no puede tener más de 20 caracteres.',
            'rut_taller.unique' => 'Este RUT de taller ya está registrado.',
            'domain.unique' => 'Este dominio ya está registrado.',
            'plan.required' => 'El plan de suscripción es obligatorio.',
            'status.required' => 'El estado del servicio es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'subscription_ends_at.date' => 'La fecha de fin de suscripción debe ser una fecha válida.',
            'admin_name.required' => 'El nombre del administrador es obligatorio.',
            'admin_email.required' => 'El correo electrónico del administrador es obligatorio.',
            'admin_email.email' => 'El correo electrónico del administrador debe ser una dirección de correo válida.',
            'admin_email.unique' => 'Este correo electrónico ya está registrado.',
            'admin_password.required' => 'La contraseña del administrador es obligatoria.',
            'admin_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
