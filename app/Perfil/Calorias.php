<?php

namespace App\Perfil;

/** Calorías diarias con la fórmula de Mifflin-St Jeor, el nivel de actividad y la meta. */
final class Calorias
{
    public const ACTIVIDAD = ['sedentaria' => 1.2, 'ligera' => 1.375, 'moderada' => 1.55, 'alta' => 1.725];

    public const META = ['perder' => 0.85, 'mantener' => 1.0, 'ganar' => 1.10];

    public const MINIMO = 1200;

    public static function objetivo(string $sexo, int $edad, float $alturaCm, float $pesoKg, string $actividad, string $meta): int
    {
        $basal = 10 * $pesoKg + 6.25 * $alturaCm - 5 * $edad + ($sexo === 'hombre' ? 5 : -161);
        $total = $basal * self::ACTIVIDAD[$actividad] * self::META[$meta];

        return max(self::MINIMO, (int) (round($total / 50) * 50));
    }
}
