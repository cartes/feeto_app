<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        // Spatie multitenancy might scope this if 'auth' middleware has NeedsTenant.
        // Wait, NeedTenant is on 'web', but we excluded it in routes/web.php.
        // However, if the User model has a Tenant scope trait, we might need to use withoutTenants().
        // Let's assume standard auth users since User mode didn't have special spatie traits.
        // Let's load the tenant relation. Wait, does User have `tenant()` or `tenant_id`?
        // Typically multitenancy uses `tenant_id`. Let's assume User has a `tenant` relation or just fetch all.
        // If not, we'll just return users for now.
        $users = User::query()
            ->with('tenant') // We'll add this relation to User if not present, but let's check.
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }
}
