<?php

namespace App\Demo;

use App\Models\User;

/** Borra las copias de la demo caducadas y, si hay demasiadas vivas, las más antiguas. */
class LimpiarCuentasDemo
{
    public const TOPE = 200;

    /** @return int cuántas cuentas ha borrado */
    public function ejecutar(int $tope = self::TOPE): int
    {
        $borradas = 0;

        // Al borrar el usuario se borran en cascada sus recetas, planes y listas.
        foreach (User::where('es_demo', true)->where('caduca_en', '<', now())->get() as $u) {
            $u->delete();
            $borradas++;
        }

        $sobran = User::where('es_demo', true)->count() - $tope;
        if ($sobran > 0) {
            foreach (User::where('es_demo', true)->oldest('id')->limit($sobran)->get() as $u) {
                $u->delete();
                $borradas++;
            }
        }

        return $borradas;
    }
}
