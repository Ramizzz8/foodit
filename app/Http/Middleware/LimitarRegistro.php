<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/** Como mucho cinco cuentas nuevas por hora desde la misma IP (Fortify no limita el registro). */
class LimitarRegistro
{
    public const POR_HORA = 5;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post') || ! $request->is('register')) {
            return $next($request);
        }

        $clave = 'registro|'.$request->ip();
        if (RateLimiter::tooManyAttempts($clave, self::POR_HORA)) {
            abort(429, 'Demasiadas cuentas nuevas desde esta conexión. Prueba dentro de una hora.');
        }

        $respuesta = $next($request);

        // Solo cuenta el alta que se ha creado de verdad, no un formulario con errores.
        if (auth()->check() && $respuesta->isRedirection()) {
            RateLimiter::hit($clave, 3600);
        }

        return $respuesta;
    }
}
