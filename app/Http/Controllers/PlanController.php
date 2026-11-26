<?php

namespace App\Http\Controllers;

use App\Cocina\CalculadoraReceta;
use App\Models\ComidaPlan;
use App\Models\Receta;
use App\Models\User;
use App\Plan\GenerarSemana;
use App\Plan\ResumenSemana;
use App\Plan\Semana;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public const MOMENTOS = ['desayuno', 'comida', 'cena'];

    public function __construct(private CalculadoraReceta $calculadora, private ResumenSemana $resumen) {}

    public function semana(Request $request, ?string $fecha = null): Response|RedirectResponse
    {
        $u = $request->user();
        $dia = $fecha === null ? CarbonImmutable::today() : Semana::fecha($fecha);
        $lunes = $dia->startOfWeek();
        if ($fecha !== null && ! $dia->equalTo($lunes)) {
            return redirect("/plan/{$lunes->toDateString()}");
        }

        $comidas = ResumenSemana::comidas($u, $lunes);
        $recetas = Receta::visiblesPara($u)->with('ingredientes.alimento.producto')->orderBy('titulo')->get();

        return Inertia::render('plan/semana', [
            'lunes' => $lunes->toDateString(),
            'hoy' => CarbonImmutable::today()->toDateString(),
            'momentos' => array_values(array_filter(self::MOMENTOS, fn ($m) => $m !== 'desayuno' || $u->planifica_desayuno)),
            'comidas' => $comidas->mapWithKeys(fn (ComidaPlan $c) => [
                $c->fecha->toDateString().'|'.$c->momento => [
                    'tipo' => $c->tipo,
                    'raciones' => $c->raciones,
                    'nota' => $c->nota,
                    'receta' => $c->receta ? $this->receta($c->receta, $u) : null,
                ],
            ]),
            'recetas' => $recetas->map(fn (Receta $r) => [...$this->receta($r, $u), 'etiquetas' => $r->etiquetas, 'minutos' => $r->minutos]),
            'resumen' => $this->resumen->para($u, $lunes, $comidas),
        ]);
    }

    public function poner(Request $request, string $fecha, string $momento): RedirectResponse
    {
        $dia = Semana::fecha($fecha);
        abort_unless(in_array($momento, self::MOMENTOS, true), 404);
        $u = $request->user();

        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['receta', 'fuera', 'sobras'])],
            'receta_id' => [
                Rule::requiredIf($request->input('tipo') === 'receta'),
                'nullable',
                'integer',
                fn ($campo, $valor, $falla) => $valor && ! Receta::visiblesPara($u)->whereKey($valor)->exists() ? $falla('Esa receta no existe.') : null,
            ],
            'raciones' => ['nullable', 'numeric', 'min:0.5', 'max:10'],
            'nota' => ['nullable', 'string', 'max:100'],
        ]);

        $u->comidasPlan()->updateOrCreate(
            ['fecha' => $dia->toDateString(), 'momento' => $momento],
            [
                'tipo' => $datos['tipo'],
                'receta_id' => $datos['tipo'] === 'receta' ? $datos['receta_id'] : null,
                'raciones' => $datos['raciones'] ?? 1,
                'nota' => $datos['nota'] ?? null,
            ],
        );

        return back();
    }

    public function quitar(Request $request, string $fecha, string $momento): RedirectResponse
    {
        $request->user()->comidasPlan()->whereDate('fecha', Semana::fecha($fecha)->toDateString())->where('momento', $momento)->delete();

        return back();
    }

    /** «Proponerme la semana»: sustituye la semana por una generada por Foodit. */
    public function generar(Request $request, string $fecha, GenerarSemana $generar): RedirectResponse
    {
        $lunes = Semana::fecha($fecha)->startOfWeek();
        $semilla = (int) ($request->validate(['semilla' => ['nullable', 'integer', 'min:0']])['semilla'] ?? random_int(0, PHP_INT_MAX));
        $semana = $generar->para($request->user(), $lunes, $semilla);
        $generar->aplicar($request->user(), $lunes, $semana);

        return back()->with('exito', 'Semana propuesta: '.count($semana['comidas']).' comidas por unos '.number_format($semana['coste'], 2, ',', '.').' €.')
            ->with('aviso', $semana['aviso']);
    }

    public function copiarAnterior(Request $request, string $fecha): RedirectResponse
    {
        $u = $request->user();
        $lunes = Semana::fecha($fecha)->startOfWeek();
        $ocupados = ResumenSemana::comidas($u, $lunes)->map(fn ($c) => $c->fecha->toDateString().'|'.$c->momento)->all();
        $copiadas = 0;

        foreach (ResumenSemana::comidas($u, $lunes->subWeek()) as $c) {
            $fechaNueva = $c->fecha->addWeek()->toDateString();
            if (! in_array("{$fechaNueva}|{$c->momento}", $ocupados, true)) {
                $u->comidasPlan()->create([...$c->only('momento', 'tipo', 'receta_id', 'raciones', 'nota'), 'fecha' => $fechaNueva]);
                $copiadas++;
            }
        }

        return back()->with('exito', $copiadas === 0 ? 'No había nada que copiar en los huecos libres.' : "Copiadas {$copiadas} comidas de la semana anterior.");
    }

    /** @return array<string, mixed> */
    private function receta(Receta $r, User $u): array
    {
        return [
            'id' => $r->id,
            'titulo' => $r->titulo,
            'foto' => $r->foto_path ? Storage::url($r->foto_path) : $r->foto_url,
            'por_racion' => collect($this->calculadora->porRacion($r, $u))->except('faltan')->all(),
        ];
    }
}
