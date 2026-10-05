<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tenant;
use App\Services\TenantSetupChecklistService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantSetupChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $tenant = Tenant::current();

        return $user !== null && $tenant !== null
            && ($user->is_super_admin || $user->tenant_id === $tenant->id)
            && $user->can('users.manage');
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'step' => ['required', 'string', Rule::in(TenantSetupChecklistService::STEPS)],
            'completed' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'step.required' => 'Selecciona el paso que quieres actualizar.',
            'step.string' => 'El paso debe ser válido.',
            'step.in' => 'Este paso no pertenece a la lista de preparación.',
            'completed.required' => 'Indica si el paso está completado.',
            'completed.boolean' => 'El estado del paso debe ser válido.',
        ];
    }
}
