<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Las copias de la demo son temporales: no se puede cambiar su acceso (email, contraseña, 2FA, llaves) ni borrarlas. */
class ProtegerDemo
{
    public const AVISO = 'Desactivado en la demo: esta cuenta es temporal y se borra en 24 horas.';

    private const RUTAS = [
        'profile.update', 'profile.destroy', 'user-password.update',
        'two-factor.enable', 'two-factor.confirm', 'two-factor.disable', 'two-factor.regenerate-recovery-codes',
        'passkey.store', 'passkey.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! $request->user()?->es_demo || ! in_array($request->route()?->getName(), self::RUTAS, true)) {
            return $next($request);
        }

        return $request->expectsJson()
            ? response()->json(['message' => self::AVISO], 403)
            : back()->withErrors(['demo' => self::AVISO]);
    }
}
