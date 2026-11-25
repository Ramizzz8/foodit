<?php

namespace App\Cocina;

use App\Models\AlimentoProductoUsuario;
use App\Models\IngredienteReceta;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use App\Models\User;

/**
 * Coste y nutrición de una ración. El coste es lo que te comes (proporcional a lo usado), no lo que pagas
 * en la tienda. Lo que no se puede calcular se dice en «faltan» en vez de contarlo como cero.
 */
class CalculadoraReceta
{
    /** @var array<int, array<int, ProductoMercadona|null>> productos elegidos por usuario y alimento */
    private array $elegidos = [];

    /**
     * @return array{coste: float, kcal: float, proteinas: float, hidratos: float, grasas: float, completo: bool,
     *               faltan: list<array{texto: string, motivo: 'sin_alimento'|'sin_cantidad'|'sin_conversion'|'sin_producto'}>}
     */
    public function porRacion(Receta $r, ?User $u): array
    {
        $total = ['coste' => 0.0, 'kcal' => 0.0, 'proteinas' => 0.0, 'hidratos' => 0.0, 'grasas' => 0.0];
        $faltan = [];

        foreach ($r->ingredientes as $i) {
            $problema = $this->sumar($i, $u, $total);
            if ($problema !== null) {
                $faltan[] = ['texto' => $i->texto, 'motivo' => $problema];
            }
        }

        $raciones = max(1, $r->raciones);

        return [
            'coste' => self::redondear($total['coste'] / $raciones, 2),
            'kcal' => self::redondear($total['kcal'] / $raciones, 1),
            'proteinas' => self::redondear($total['proteinas'] / $raciones, 2),
            'hidratos' => self::redondear($total['hidratos'] / $raciones, 2),
            'grasas' => self::redondear($total['grasas'] / $raciones, 2),
            'completo' => $faltan === [],
            'faltan' => $faltan,
        ];
    }

    /** Redondeo «de calculadora»: 0,465 € son 0,47 € aunque en coma flotante sea 0,46499… */
    private static function redondear(float $valor, int $decimales): float
    {
        return round($valor + 1e-9, $decimales);
    }

    /** Suma el ingrediente al total y devuelve el motivo por el que no se ha podido calcular algo, si lo hay. */
    private function sumar(IngredienteReceta $i, ?User $u, array &$total): ?string
    {
        // «Al gusto» (sal, pimienta…) no cuenta: es una cantidad despreciable.
        if ($i->unidad === 'al_gusto') {
            return null;
        }
        $a = $i->alimento;
        if ($a === null) {
            return 'sin_alimento';
        }

        $gramos = Unidades::aGramos($i->cantidad, $i->unidad, $a);
        if ($gramos === null) {
            return $i->cantidad === null ? 'sin_cantidad' : 'sin_conversion';
        }

        foreach (['kcal', 'proteinas', 'hidratos', 'grasas'] as $n) {
            $total[$n] += ($a->{$n} ?? 0) * $gramos / 100;
        }

        $producto = $this->producto($i, $u);
        if ($producto === null) {
            return 'sin_producto';
        }
        $envases = Envases::calcular($gramos, $producto, $a);
        if ($envases === null) {
            return 'sin_conversion';
        }
        $total['coste'] += $envases['coste_usado'];

        return null;
    }

    private function producto(IngredienteReceta $i, ?User $u): ?ProductoMercadona
    {
        if ($u !== null) {
            $this->elegidos[$u->id] ??= AlimentoProductoUsuario::where('user_id', $u->id)
                ->pluck('producto_mercadona_id', 'alimento_id')
                ->map(fn ($id) => ProductoMercadona::find($id))
                ->all();
            if (isset($this->elegidos[$u->id][$i->alimento_id])) {
                return $this->elegidos[$u->id][$i->alimento_id];
            }
        }

        return $i->alimento->producto;
    }
}
