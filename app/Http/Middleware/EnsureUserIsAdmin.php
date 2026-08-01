<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Permite el paso solo a administradores activos.
     * En API responde 403 JSON; en web redirige al dashboard con error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'No autenticado.'], 401)
                : redirect()->route('login');
        }

        if (! $user->is_active) {
            auth()->logout();

            return $request->expectsJson()
                ? response()->json(['message' => 'Cuenta desactivada.'], 403)
                : redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
                ]);
        }

        if (! $user->isAdmin()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'No tienes permisos de administrador.'], 403)
                : redirect()->route('dashboard')->with('error', 'No tienes permisos para acceder al panel de administración.');
        }

        return $next($request);
    }
}
