<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    /** Muestra el formulario de cambio de contraseña obligatorio. */
    public function show(): View
    {
        return view('auth.change-password');
    }

    /** Guarda la nueva contraseña y limpia el flag force_password_change. */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();
        $user->password = $request->password;          // cast 'hashed'
        $user->force_password_change = false;          // asignación explícita
        $user->setRememberToken(Str::random(60));
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Contraseña actualizada correctamente.');
    }
}
