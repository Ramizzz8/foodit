<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Hasta completar el alta guiada, la app lleva a la bienvenida. Las cuentas de ejemplo ya vienen completas. */
class PerfilCompleto
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();

        if ($u && ! $u->es_demo && $u->perfil_completo_en === null) {
            return redirect('/bienvenida/1');
        }

        return $next($request);
    }
}
