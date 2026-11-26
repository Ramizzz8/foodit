<?php

namespace App\Http\Controllers;

use App\Cocina\CalculadoraReceta;
use App\Cocina\Unidades;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RecetaController extends Controller
{
    public const MAX_FOTOS = 50;

    public const ETIQUETAS = ['rápida', 'económica', 'vegetariana', 'legumbre', 'pescado', 'carne', 'pasta y arroz', 'ligera', 'desayuno', 'sin gluten'];

    public function __construct(private CalculadoraReceta $calculadora) {}

    public function index(Request $request): Response
    {
        $u = $request->user();
        $guardadas = $u->recetasGuardadas()->pluck('recetas.id')->all();

        $recetas = Receta::visiblesPara($u)
            ->with('ingredientes.alimento.producto')
            ->when($request->query('q'), fn ($q, $t) => $q->where('titulo', 'like', "%{$t}%"))
            ->when($request->query('etiqueta'), fn ($q, $e) => $q->whereJsonContains('etiquetas', $e))
            ->when($request->query('solo') === 'mias', fn ($q) => $q->where('user_id', $u->id))
            ->when($request->query('solo') === 'guardadas', fn ($q) => $q->whereIn('id', $guardadas))
            ->orderByRaw('user_id is null')
            ->orderBy('titulo')
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Receta $r) => $this->tarjeta($r, $u, $guardadas));

        return Inertia::render('recetas/index', [
            'recetas' => $recetas,
            'filtros' => $request->only('q', 'etiqueta', 'solo'),
            'etiquetas' => self::ETIQUETAS,
        ]);
    }

    public function show(Request $request, Receta $receta): Response
    {
        $u = $request->user();
        $receta->load('ingredientes.alimento.producto');

        return Inertia::render('recetas/show', [
            'receta' => [
                ...$this->tarjeta($receta, $u, $u->recetasGuardadas()->pluck('recetas.id')->all()),
                'descripcion' => $receta->descripcion,
                'raciones' => $receta->raciones,
                'pasos' => $receta->pasos,
                'foto_credito' => $receta->foto_credito,
                'ingredientes' => $receta->ingredientes->map(fn ($i) => [
                    'id' => $i->id, 'texto' => $i->texto, 'nota' => $i->nota,
                ]),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('recetas/form', ['receta' => null, 'etiquetas' => self::ETIQUETAS, 'unidades' => Unidades::LISTA]);
    }

    public function store(Request $request): RedirectResponse
    {
        $receta = DB::transaction(fn () => $this->guardarDatos(new Receta(['user_id' => $request->user()->id]), $this->validar($request), $request));

        return to_route('recetas.show', $receta)->with('exito', 'Receta guardada.');
    }

    public function edit(Request $request, Receta $receta): Response
    {
        $this->soloPropias($request->user(), $receta);
        $receta->load('ingredientes.alimento');

        return Inertia::render('recetas/form', [
            'receta' => [
                ...$receta->only('id', 'titulo', 'descripcion', 'raciones', 'minutos', 'dificultad', 'etiquetas', 'pasos'),
                'foto' => $this->foto($receta),
                'ingredientes' => $receta->ingredientes->map(fn ($i) => [
                    ...$i->only('texto', 'alimento_id', 'cantidad', 'unidad', 'nota'),
                    'alimento' => $i->alimento?->nombre,
                ]),
            ],
            'etiquetas' => self::ETIQUETAS,
            'unidades' => Unidades::LISTA,
        ]);
    }

    public function update(Request $request, Receta $receta): RedirectResponse
    {
        $this->soloPropias($request->user(), $receta);
        DB::transaction(fn () => $this->guardarDatos($receta, $this->validar($request), $request));

        return to_route('recetas.show', $receta)->with('exito', 'Cambios guardados.');
    }

    public function destroy(Request $request, Receta $receta): RedirectResponse
    {
        $this->soloPropias($request->user(), $receta);
        $receta->delete();

        return to_route('recetas.index')->with('exito', 'Receta borrada.');
    }

    public function duplicar(Request $request, Receta $receta): RedirectResponse
    {
        $copia = DB::transaction(function () use ($request, $receta) {
            $copia = $receta->replicate(['user_id', 'receta_origen_id']);
            $copia->user_id = $request->user()->id;
            $copia->receta_origen_id = $receta->id;
            $copia->save();
            foreach ($receta->ingredientes as $i) {
                $copia->ingredientes()->create($i->only('alimento_id', 'texto', 'cantidad', 'unidad', 'nota', 'orden'));
            }

            return $copia;
        });

        return to_route('recetas.edit', $copia)->with('exito', 'Copia creada: ahora es tuya y puedes cambiarla.');
    }

    public function guardar(Request $request, Receta $receta): RedirectResponse
    {
        $request->user()->recetasGuardadas()->syncWithoutDetaching([$receta->id]);

        return back()->with('exito', 'Guardada en tu recetario.');
    }

    public function olvidar(Request $request, Receta $receta): RedirectResponse
    {
        $request->user()->recetasGuardadas()->detach($receta->id);

        return back()->with('exito', 'Quitada de tu recetario.');
    }

    /** @return array<string, mixed> */
    private function tarjeta(Receta $r, User $u, array $guardadas): array
    {
        return [
            ...$r->only('id', 'titulo', 'minutos', 'dificultad', 'etiquetas'),
            'foto' => $this->foto($r),
            'es_mia' => $r->user_id === $u->id,
            'guardada' => in_array($r->id, $guardadas, true),
            'por_racion' => $this->calculadora->porRacion($r, $u),
        ];
    }

    private function foto(Receta $r): ?string
    {
        return $r->foto_path ? Storage::url($r->foto_path) : $r->foto_url;
    }

    private function soloPropias(User $u, Receta $r): void
    {
        abort_if($r->user_id !== $u->id, 403, 'Las recetas de Foodit no se pueden cambiar: duplícala para tener tu versión.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'raciones' => ['required', 'integer', 'min:1', 'max:20'],
            'minutos' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'dificultad' => ['required', Rule::in(['facil', 'media', 'dificil'])],
            'etiquetas' => ['array'],
            'etiquetas.*' => [Rule::in(self::ETIQUETAS)],
            'pasos' => ['required', 'array', 'min:1', 'max:30'],
            'pasos.*' => ['required', 'string', 'max:1000'],
            'ingredientes' => ['required', 'array', 'min:1', 'max:40'],
            'ingredientes.*.texto' => ['required', 'string', 'max:150'],
            'ingredientes.*.alimento_id' => ['nullable', 'integer', 'exists:alimentos,id'],
            'ingredientes.*.cantidad' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'ingredientes.*.unidad' => ['required', Rule::in(Unidades::LISTA)],
            'ingredientes.*.nota' => ['nullable', 'string', 'max:100'],
            // Las cuentas de ejemplo no suben fotos; las reales, hasta 50 recetas con foto.
            'foto' => $request->user()->es_demo
                ? ['prohibited']
                : ['nullable', 'image', 'max:5120', function (string $campo, mixed $valor, \Closure $falla) use ($request) {
                    if ($valor && $request->user()->recetas()->whereNotNull('foto_path')->count() >= self::MAX_FOTOS) {
                        $falla('Has llegado al máximo de '.self::MAX_FOTOS.' recetas con foto.');
                    }
                }],
        ]);
    }

    private function guardarDatos(Receta $receta, array $datos, Request $request): Receta
    {
        $receta->fill([
            ...collect($datos)->only('titulo', 'descripcion', 'raciones', 'minutos', 'dificultad')->all(),
            'slug' => Str::slug($datos['titulo']),
            'etiquetas' => array_values($datos['etiquetas'] ?? []),
            'pasos' => array_values($datos['pasos']),
        ]);
        $fotoAnterior = null;
        if ($request->hasFile('foto')) {
            $fotoAnterior = $receta->foto_path;
            $receta->foto_path = $request->file('foto')->store('recetas', 'public');
            $receta->foto_url = null;
            $receta->foto_credito = null;
        }
        $receta->save();
        Receta::borrarFotoSiSobra($fotoAnterior);

        $receta->ingredientes()->delete();
        foreach (array_values($datos['ingredientes']) as $orden => $i) {
            $receta->ingredientes()->create([
                'texto' => $i['texto'],
                'alimento_id' => $i['alimento_id'] ?? null,
                'cantidad' => $i['cantidad'] ?? null,
                'unidad' => $i['unidad'],
                'nota' => $i['nota'] ?? null,
                'orden' => $orden,
            ]);
        }

        return $receta;
    }
}
