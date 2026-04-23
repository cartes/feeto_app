<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantUserRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TenantUserController extends Controller
{
    public function index(): Response
    {
        $tenant = Tenant::current();

        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->get(['id', 'name', 'email', 'created_at']);

        $roles = Role::query()
            ->get(['id', 'name']);

        return Inertia::render('Users/Index', [
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
            ]),
            'roles' => $roles,
            'planMaxUsers' => $tenant->userLimit(),
            'currentCount' => $users->count(),
        ]);
    }

    public function store(StoreTenantUserRequest $request): RedirectResponse
    {
        $tenant = Tenant::current();

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'tenant_id' => $tenant->id,
        ]);

        $user->assignRole($request->validated('role'));

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $tenant = Tenant::current();

        abort_if($user->tenant_id !== $tenant->id, 403, 'No puedes eliminar usuarios de otro taller.');
        abort_if($user->hasRole('Admin') && $tenant->users()->count() === 1, 422, 'No puedes eliminar al único administrador del taller.');

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}
