<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view or redirect to home with modal trigger.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('home', ['login' => 1]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->is_super_admin) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->tenant_id) {
            $user->load('tenant');

            if ($user->tenant && $user->tenant->slug) {
                return redirect()->route('taller.dashboard', ['tenantBySlug' => $user->tenant->slug]);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
