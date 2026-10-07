<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantWelcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenant = Tenant::current();
        $user = $this->user();

        return $tenant !== null && $user !== null
            && ($user->is_super_admin || $user->tenant_id === $tenant->id)
            && $user->can('users.manage');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('whatsapp_number') && trim((string) $this->input('whatsapp_number')) === '') {
            $this->merge(['whatsapp_number' => null]);
        }
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        $rules = ['action' => ['required', Rule::in(['save', 'defer', 'finish'])]];
        if ($this->input('action') !== 'save') {
            return $rules;
        }

        $rules['step'] = ['required', 'integer', 'between:1,3'];

        return [...$rules, ...match ((int) $this->input('step')) {
            1 => [
                'name' => ['required', 'string', 'max:255'],
                'comuna' => ['required', 'string', 'max:100'],
                'address' => ['nullable', 'string', 'max:255'],
            ],
            2 => [
                'phone' => ['required', 'string', 'max:50', 'regex:/^(?=(?:\D*\d){7,})\+?[0-9\s()\-]{7,50}$/'],
                'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^(?=(?:\D*\d){7,})\+?[0-9\s\-]{7,20}$/'],
                'email' => ['nullable', 'email', 'max:255'],
            ],
            3 => ['description' => ['required', 'string', 'max:500']],
            default => [],
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'action.required' => 'Selecciona una acción para continuar.',
            'action.in' => 'La acción seleccionada no es válida.',
            'step.required' => 'Selecciona el paso que quieres guardar.',
            'step.integer' => 'El paso seleccionado no es válido.',
            'step.between' => 'El paso seleccionado no es válido.',
            'name.required' => 'Ingresa el nombre de tu taller.',
            'comuna.required' => 'Ingresa la comuna o ciudad de tu taller.',
            'phone.required' => 'Ingresa un teléfono de contacto.',
            'phone.regex' => 'Ingresa un teléfono válido, incluyendo su código de país.',
            'whatsapp_number.regex' => 'Ingresa un número de WhatsApp válido.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'description.required' => 'Agrega una descripción para tu página.',
            'description.max' => 'La descripción debe tener hasta 500 caracteres.',
        ];
    }
}
