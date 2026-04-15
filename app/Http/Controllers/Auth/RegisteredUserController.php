<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response|RedirectResponse
    {
        // Redirigir al landing del tenant si hay un tenant actual
        if (Tenant::checkCurrent()) {
            return Inertia::render('Auth/Register', [
                'tenantSlug' => Tenant::current()->slug,
            ]);
        }

        // Sin contexto de tenant - redirigir al home
        return redirect()->route('home');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Requiere contexto de tenant para registrarse
        if (! Tenant::checkCurrent()) {
            throw ValidationException::withMessages([
                'email' => ['Debes registrarte desde la página de un taller.'],
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => Tenant::current()->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('taller.dashboard', ['tenantBySlug' => Tenant::current()->slug]);
    }
}
