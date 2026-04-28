<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tenant;
use App\Services\TenantRoleCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateTenantRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->checkPermissionTo('roles.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenant = Tenant::current();
        $validPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', TenantRoleCatalog::permissionNames())
            ->pluck('name')
            ->all();
        $role = $this->route('role');
        $roleId = is_object($role) ? $role->id : $role;
        $currentRole = Role::query()
            ->where('guard_name', 'web')
            ->where(fn (Builder $query) => $query
                ->where('tenant_id', $tenant?->id)
                ->orWhere(fn (Builder $globalQuery) => $globalQuery
                    ->whereNull('tenant_id')
                    ->whereIn('name', TenantRoleCatalog::systemRoles())))
            ->find($roleId);

        $nameRules = [
            'required',
            'string',
            'max:255',
        ];

        if ($currentRole !== null && TenantRoleCatalog::isSystemRole($currentRole->name)) {
            $nameRules[] = Rule::in([$currentRole->name]);
        } else {
            $nameRules[] = Rule::notIn(TenantRoleCatalog::systemRoles());
            $nameRules[] = Rule::unique('roles', 'name')
                ->where(fn ($query) => $query
                    ->where('tenant_id', $tenant?->id)
                    ->where('guard_name', 'web'))
                ->ignore($roleId);
        }

        return [
            'name' => $nameRules,
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'distinct', Rule::in($validPermissions)],
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
            'name.not_in' => 'Ese nombre está reservado para un rol del sistema.',
            'permissions.array' => 'Debes enviar los permisos en un formato válido.',
            'permissions.required' => 'Debes asignar al menos un permiso.',
            'permissions.min' => 'Debes asignar al menos un permiso.',
            'permissions.*.distinct' => 'No puedes repetir permisos en el mismo rol.',
            'permissions.*.in' => 'Uno o más permisos seleccionados no son válidos.',
        ];
    }
}
