<?php

namespace App\Http\Controllers;

use App\Cocina\CalculadoraReceta;
use App\Cocina\Unidades;
use App\Models\Alimento;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

/** La landing: un tique de la semana hecho con recetas y precios reales, que el visitante puede cambiar. */
class PortadaController extends Controller
{
    /** Cada día, tres cenas para elegir (slugs de recetas de Foodit). */
    private const SEMANA = [
        'Lunes' => ['crema-de-calabacin', 'lentejas-estofadas', 'tortilla-de-patatas'],
        'Martes' => ['pollo-al-ajillo', 'revuelto-de-champinones', 'ensalada-mixta'],
        'Miércoles' => ['merluza-en-salsa-verde', 'atun-con-tomate', 'pisto-con-huevo'],
        'Jueves' => ['garbanzos-con-espinacas', 'fajitas-de-pollo', 'crema-de-calabaza-y-zanahoria'],
        'Viernes' => ['hamburguesas-caseras', 'espaguetis-carbonara', 'ensalada-de-pasta'],
        'Sábado' => ['paella-de-pollo-y-verduras', 'fideua-de-sepia-y-gambas', 'arroz-con-verduras'],
        'Domingo' => ['salmon-al-horno-con-patatas', 'sopa-de-fideos', 'gazpacho-andaluz'],
    ];

    public function __invoke(CalculadoraReceta $calculadora): Response
    {
        $recetas = Receta::whereNull('user_id')
            ->whereIn('slug', collect(self::SEMANA)->flatten())
            ->with('ingredientes.alimento.producto')
            ->get()
            ->keyBy('slug');

        $tique = collect(self::SEMANA)->map(fn ($slugs, $dia) => [
            'dia' => $dia,
            'opciones' => collect($slugs)->filter(fn ($s) => $recetas->has($s))->map(function ($s) use ($recetas, $calculadora) {
                $r = $recetas[$s];
                $x = $calculadora->porRacion($r, null);

                return ['id' => $r->id, 'titulo' => $r->titulo, 'coste' => $x['coste'], 'kcal' => $x['kcal'], 'foto' => self::fotoPrincipal($r)];
            })->values()->all(),
        ])->filter(fn ($d) => $d['opciones'] !== [])->values();

        return Inertia::render('welcome', [
            'demo' => (bool) config('foodit.demo'),
            'registro' => Features::enabled(Features::registration()),
            'tique' => $tique,
            'preciosDe' => ProductoMercadona::max('visto_en'),
            'productos' => Alimento::whereIn('nombre', ['lenteja pardina', 'huevo', 'calabacín', 'tomate triturado', 'aceite de oliva virgen extra'])
                ->with('producto')->get()
                ->filter(fn (Alimento $a) => $a->producto?->foto_url)
                ->map(fn (Alimento $a) => ['alimento' => $a->nombre, ...$a->producto->only('nombre', 'envase', 'precio', 'cantidad_envase', 'unidad_envase', 'foto_url')])
                ->values(),
        ]);
    }

    /** La foto del producto del ingrediente que más pesa en la receta: lo que compras para hacerla. */
    private static function fotoPrincipal(Receta $r): ?string
    {
        return $r->ingredientes
            ->filter(fn ($i) => $i->alimento?->producto?->foto_url && $i->unidad !== 'al_gusto')
            ->sortByDesc(fn ($i) => Unidades::aGramos($i->cantidad, $i->unidad, $i->alimento) ?? 0)
            ->first()?->alimento->producto->foto_url;
    }
}
