<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Headers de seguridad HTTP aplicados a todas las respuestas.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevenir clickjacking — no permitir que el sitio se muestre en iframes
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevenir MIME sniffing — forzar el tipo de contenido declarado
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Protección XSS legacy para navegadores antiguos
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Controlar qué información de referencia se envía
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Deshabilitar APIs del navegador que no se usan (cámara, mic, geolocalización)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Prevenir que se almacene información sensible en caché del navegador
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if (app()->environment('local')) {
            // Relaxed CSP for local development (Vite HMR)
            $response->headers->set('Content-Security-Policy', "default-src * 'unsafe-inline' 'unsafe-eval' data: blob: ws: wss:;");
        } else {
            // Content Security Policy — strict for production
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data:",
                "connect-src 'self'",
                "frame-src 'none'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
