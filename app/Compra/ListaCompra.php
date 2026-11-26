<?php

namespace App\Compra;

use App\Cocina\Envases;
use App\Cocina\Unidades;
use App\Models\Alimento;
use App\Models\PrecioHistorico;
use App\Models\ProductoMercadona;
use App\Models\User;
use App\Plan\ResumenSemana;
use Carbon\CarbonImmutable;

/**
 * La lista de la compra de una semana del plan: cada alimento sumado, pasado a envases de Mercadona
 * y agrupado por pasillos. Dos totales: lo que pagas en la tienda (envases enteros) y lo que te comes.
 */
class ListaCompra
{
    /**
     * @return array{grupos: list<array{categoria: string, lineas: list<array<string, mixed>>}>, sin_producto: list<array<string, mixed>>,
     *               al_gusto: list<string>, extras: list<array{id: int, texto: string, tachado: bool}>, total_tienda: float, total_comido: float, precios_de: ?string}
     */
    public function para(User $u, CarbonImmutable $lunes): array
    {
        [$porAlimento, $sinAlimento, $alGusto] = $this->sumar($u, $lunes);
        $tachados = $u->listaMarcas()->whereDate('lunes', $lunes->toDateString())->pluck('tachado', 'alimento_id');
        $enDespensa = $u->despensa()->pluck('alimentos.id')->all();

        $conProducto = [];
        $sinProducto = $sinAlimento;
        $totalTienda = 0.0;
        $totalComido = 0.0;
        $preciosDe = null;

        foreach ($porAlimento as ['alimento' => $a, 'gramos' => $gramos]) {
            $p = $a->productoPara($u);
            $envases = $p ? Envases::calcular($gramos, $p, $a) : null;
            $linea = ['alimento_id' => $a->id, 'alimento' => $a->nombre, 'gramos' => round($gramos, 1), 'tachado' => (bool) ($tachados[$a->id] ?? false),
                'quiza_en_despensa' => in_array($a->id, $enDespensa, true)];

            if ($envases === null) {
                $sinProducto[] = $linea + ['producto' => null, 'envases' => null, 'comprado' => null, 'sobra' => null, 'unidad' => null, 'coste' => null];

                continue;
            }

            $conProducto[] = ['categoria' => $p->categoria, 'orden' => $p->orden_pasillo, 'linea' => $linea + [
                'producto' => [...$p->only('id', 'nombre', 'envase', 'foto_url', 'precio', 'cantidad_envase', 'unidad_envase'), 'cambio' => $this->cambioPrecio($p)],
                'envases' => $envases['envases'],
                'comprado' => $envases['comprado'],
                'sobra' => $envases['sobra'],
                'unidad' => $envases['unidad'],
                'coste' => $envases['coste_compra'],
            ]];
            $totalTienda += $envases['coste_compra'];
            $totalComido += $envases['coste_usado'];
            $preciosDe = max($preciosDe, $p->visto_en?->toDateString());
        }

        $grupos = collect($conProducto)
            ->sortBy([['orden', 'asc'], ['categoria', 'asc']])
            ->groupBy('categoria')
            ->map(fn ($g, $categoria) => ['categoria' => $categoria, 'lineas' => $g->pluck('linea')->sortBy('alimento')->values()->all()])
            ->values()
            ->all();

        return [
            'grupos' => $grupos,
            'sin_producto' => collect($sinProducto)->sortBy('alimento')->values()->all(),
            'al_gusto' => collect($alGusto)->unique()->sort()->values()->all(),
            'extras' => $u->listaExtras()->whereDate('lunes', $lunes->toDateString())->orderBy('id')->get()
                ->map(fn ($e) => ['id' => $e->id, 'texto' => $e->texto, 'tachado' => $e->tachado])->all(),
            'total_tienda' => round($totalTienda + 1e-9, 2),
            'total_comido' => round($totalComido + 1e-9, 2),
            'precios_de' => $preciosDe,
        ];
    }

    /**
     * Suma los gramos de cada alimento de las recetas planificadas, escalados a las raciones de cada comida.
     *
     * @return array{0: array<int, array{alimento: Alimento, gramos: float}>, 1: list<array<string, mixed>>, 2: list<string>}
     */
    private function sumar(User $u, CarbonImmutable $lunes): array
    {
        $porAlimento = [];
        $sinAlimento = [];
        $alGusto = [];

        foreach (ResumenSemana::comidas($u, $lunes) as $c) {
            if ($c->tipo !== 'receta' || $c->receta === null) {
                continue;
            }
            $escala = $c->raciones / max(1, $c->receta->raciones);

            foreach ($c->receta->ingredientes as $i) {
                if ($i->unidad === 'al_gusto') {
                    $alGusto[] = $i->alimento?->nombre ?? $i->texto;

                    continue;
                }
                $gramos = $i->alimento ? Unidades::aGramos($i->cantidad, $i->unidad, $i->alimento) : null;
                if ($gramos === null) {
                    $sinAlimento[$i->texto] = ['alimento_id' => $i->alimento_id, 'alimento' => $i->alimento?->nombre ?? $i->texto, 'gramos' => null, 'tachado' => false, 'quiza_en_despensa' => false,
                        'producto' => null, 'envases' => null, 'comprado' => null, 'sobra' => null, 'unidad' => null, 'coste' => null];

                    continue;
                }
                $porAlimento[$i->alimento_id] ??= ['alimento' => $i->alimento, 'gramos' => 0.0];
                $porAlimento[$i->alimento_id]['gramos'] += $gramos * $escala;
            }
        }

        return [$porAlimento, array_values($sinAlimento), $alGusto];
    }

    /** Cuánto ha cambiado el precio en los últimos 7 días (null si no ha cambiado). */
    private function cambioPrecio(ProductoMercadona $p): ?float
    {
        $historico = PrecioHistorico::where('producto_mercadona_id', $p->id)->orderByDesc('fecha')->orderByDesc('id')->limit(2)->get();
        if ($historico->count() < 2 || $historico[0]->fecha->lt(CarbonImmutable::today()->subDays(7))) {
            return null;
        }

        return round($historico[0]->precio - $historico[1]->precio, 2);
    }
}
