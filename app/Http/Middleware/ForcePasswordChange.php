<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Si el admin marcó al usuario para cambiar su contraseña, se le obliga a
     * establecer una nueva antes de usar cualquier módulo. Se excluyen las rutas
     * del propio cambio de contraseña y el logout para no crear un bucle.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->force_password_change) {
            $exentas = ['password.change.show', 'password.change.update', 'logout'];

            if (! $request->routeIs($exentas)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Debes cambiar tu contraseña antes de continuar.',
                        'force_password_change' => true,
                    ], 403);
                }

                return redirect()->route('password.change.show');
            }
        }

        return $next($request);
    }
}
