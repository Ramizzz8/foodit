<?php

namespace App\Plan;

use Carbon\CarbonImmutable;
use Throwable;

/** Fechas de las direcciones del plan y de la lista («2026-11-23»). */
final class Semana
{
    /** La fecha exacta o 404 si no es una fecha válida (no admite «2026-02-30»). */
    public static function fecha(string $fecha): CarbonImmutable
    {
        try {
            $dia = CarbonImmutable::createFromFormat('!Y-m-d', $fecha);
        } catch (Throwable) {
            abort(404);
        }
        abort_if($dia === null || $dia->toDateString() !== $fecha, 404);

        return $dia;
    }
}
