<?php

namespace App\Http\Controllers;

use App\Models\Alimento;
use App\Models\Receta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** La despensa y «con lo que tienes»: recetas ordenadas por lo poco que te falta (cálculo, sin IA). */
class DespensaController extends Controller
{
    public const SUGERENCIAS = 6;

    public function index(Request $request): Response
    {
        $u = $request->user();
        $despensa = $u->despensa()->get(['alimentos.id', 'alimentos.nombre']);
        $tengo = $despensa->pluck('id')->all();

        $sugerencias = $tengo === [] ? collect() : Receta::visiblesPara($u)->with('ingredientes.alimento')->get()
            ->map(function (Receta $r) use ($tengo) {
                $alimentos = $r->ingredientes->filter(fn ($i) => $i->unidad !== 'al_gusto' && $i->alimento)->pluck('alimento')->unique('id');
                [$tienes, $faltan] = $alimentos->partition(fn (Alimento $a) => in_array($a->id, $tengo, true));

                return [
                    'id' => $r->id,
                    'titulo' => $r->titulo,
                    'foto' => $r->foto_url,
                    'minutos' => $r->minutos,
                    'tienes' => $tienes->pluck('nombre')->values()->all(),
                    'faltan' => $faltan->pluck('nombre')->values()->all(),
                ];
            })
            ->filter(fn ($s) => $s['tienes'] !== [])
            ->sortBy([fn ($a, $b) => count($a['faltan']) <=> count($b['faltan']), fn ($a, $b) => count($b['tienes']) <=> count($a['tienes'])])
            ->take(self::SUGERENCIAS)
            ->values();

        return Inertia::render('despensa', [
            'despensa' => $despensa->map(fn ($a) => ['id' => $a->id, 'nombre' => $a->nombre])->values(),
            'sugerencias' => $sugerencias,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate(['alimento_id' => ['required', 'integer', 'exists:alimentos,id']]);
        $request->user()->despensa()->syncWithoutDetaching([$datos['alimento_id']]);

        return back();
    }

    public function destroy(Request $request, Alimento $alimento): RedirectResponse
    {
        $request->user()->despensa()->detach($alimento->id);

        return back();
    }
}
