<?php

namespace App\Cocina;

use App\Models\Alimento;
use App\Models\ProductoMercadona;

/** Cuántos envases de un producto hay que comprar para unos gramos de un alimento, y lo que cuesta. */
final class Envases
{
    /**
     * @return array{envases: int, comprado: float, sobra: float, unidad: string, coste_compra: float, coste_usado: float}|null
     *                                                                                                                          null si no se pueden pasar los gramos a la unidad del producto.
     */
    public static function calcular(float $gramos, ProductoMercadona $p, Alimento $a): ?array
    {
        $necesario = match ($p->unidad_envase) {
            'g' => $gramos,
            'ml' => $gramos / ($a->gramos_por_ml ?? 1.0),
            'ud' => $a->gramos_por_unidad ? $gramos / $a->gramos_por_unidad : null,
        };

        if ($necesario === null || $p->cantidad_envase <= 0) {
            return null;
        }

        // El margen evita comprar un envase de más por errores de redondeo (1000,0000001 g).
        $envases = max(1, (int) ceil($necesario / $p->cantidad_envase - 1e-6));
        $comprado = $envases * $p->cantidad_envase;

        return [
            'envases' => $envases,
            'comprado' => round($comprado, 2),
            'sobra' => round($comprado - $necesario, 2),
            'unidad' => $p->unidad_envase,
            'coste_compra' => round($envases * $p->precio, 2),
            'coste_usado' => round($p->precio * $necesario / $p->cantidad_envase, 4),
        ];
    }
}
