<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Permission;

class UpdateTenantRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('roles.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validPermissions = Permission::query()->pluck('name')->all();
        $role = $this->route('role');
        $roleId = $role?->id;

        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$roleId],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'in:'.implode(',', $validPermissions)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Ya existe un rol con este nombre.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'permissions.required' => 'Debes asignar al menos un permiso.',
            'permissions.min' => 'Debes asignar al menos un permiso.',
            'permissions.*.in' => 'Uno o más permisos seleccionados no son válidos.',
        ];
    }
}
