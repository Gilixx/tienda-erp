<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function store(Request $request): RedirectResponse
    {
        // Honeypot anti-bot: si el campo oculto tiene valor, es un bot
        if ($request->filled('website_url')) {
            abort(422);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Se verifica la cuenta DESPUÉS de autenticar: comprobar is_active antes con
        // un mensaje distinto permitía enumerar qué correos existen. Sin la contraseña
        // correcta solo se devuelve el mensaje genérico de credenciales.
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (! Auth::user()->is_active) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session (logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
