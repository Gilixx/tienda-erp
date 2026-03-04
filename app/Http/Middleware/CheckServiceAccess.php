<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckServiceAccess
{
    /**
     * Handle an incoming request.
     * Checks that the authenticated user has access to the requested service/module.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $serviceKey): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ]);
        }

        if (!$user->hasService($serviceKey)) {
            return redirect()->route('dashboard')->with('error',
                'No tienes acceso al módulo "' . $serviceKey . '". Contacta al administrador para contratar este servicio.'
            );
        }

        return $next($request);
    }
}
