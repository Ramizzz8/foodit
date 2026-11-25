<?php

namespace App\Cocina;

use App\Models\Alimento;

/** Pasa la cantidad de un ingrediente (en la unidad de la receta) a gramos de ese alimento. */
final class Unidades
{
    public const LISTA = ['g', 'kg', 'ml', 'l', 'ud', 'cda', 'cdta', 'pizca', 'al_gusto'];

    private const ML_CUCHARADA = 15;

    private const ML_CUCHARADITA = 5;

    private const GRAMOS_PIZCA = 0.5;

    /** null si no se puede saber (sin cantidad, o por unidades de un alimento sin peso por unidad). */
    public static function aGramos(?float $cantidad, string $unidad, Alimento $a): ?float
    {
        if ($unidad === 'al_gusto') {
            return 0.0;
        }
        if ($cantidad === null) {
            return null;
        }

        $densidad = $a->gramos_por_ml ?? 1.0;

        $gramos = match ($unidad) {
            'g' => $cantidad,
            'kg' => $cantidad * 1000,
            'ml' => $cantidad * $densidad,
            'l' => $cantidad * 1000 * $densidad,
            'cda' => $cantidad * self::ML_CUCHARADA * $densidad,
            'cdta' => $cantidad * self::ML_CUCHARADITA * $densidad,
            'pizca' => $cantidad * self::GRAMOS_PIZCA,
            'ud' => $a->gramos_por_unidad === null ? null : $cantidad * $a->gramos_por_unidad,
        };

        return $gramos === null ? null : round($gramos, 4);
    }
}
