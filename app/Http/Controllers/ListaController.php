<?php

namespace App\Http\Controllers;

use App\Compra\ListaCompra;
use App\Models\Alimento;
use App\Plan\Semana;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListaController extends Controller
{
    public function __construct(private ListaCompra $lista) {}

    public function semana(Request $request, ?string $fecha = null): Response|RedirectResponse
    {
        return $this->pagina($request, $fecha, 'lista/semana', '/lista');
    }

    public function imprimir(Request $request, string $fecha): Response|RedirectResponse
    {
        return $this->pagina($request, $fecha, 'lista/imprimir', '/lista', '/imprimir');
    }

    public function marcar(Request $request, string $fecha, Alimento $alimento): RedirectResponse
    {
        $datos = $request->validate(['tachado' => ['required', 'boolean']]);
        $request->user()->listaMarcas()->updateOrCreate(
            ['lunes' => self::lunes($fecha)->toDateString(), 'alimento_id' => $alimento->id],
            ['tachado' => $datos['tachado']],
        );

        return back();
    }

    public function anadirExtra(Request $request, string $fecha): RedirectResponse
    {
        $datos = $request->validate(['texto' => ['required', 'string', 'max:100']]);
        $request->user()->listaExtras()->create(['lunes' => self::lunes($fecha)->toDateString(), 'texto' => $datos['texto']]);

        return back();
    }

    public function cambiarExtra(Request $request, string $fecha, int $extra): RedirectResponse
    {
        $datos = $request->validate(['tachado' => ['required', 'boolean']]);
        $request->user()->listaExtras()->findOrFail($extra)->update($datos);

        return back();
    }

    public function quitarExtra(Request $request, string $fecha, int $extra): RedirectResponse
    {
        $request->user()->listaExtras()->findOrFail($extra)->delete();

        return back();
    }

    private function pagina(Request $request, ?string $fecha, string $componente, string $ruta, string $sufijo = ''): Response|RedirectResponse
    {
        $dia = $fecha === null ? CarbonImmutable::today() : Semana::fecha($fecha);
        $lunes = $dia->startOfWeek();
        if ($fecha !== null && ! $dia->equalTo($lunes)) {
            return redirect("{$ruta}/{$lunes->toDateString()}{$sufijo}");
        }

        return Inertia::render($componente, [
            'lunes' => $lunes->toDateString(),
            'lista' => $this->lista->para($request->user(), $lunes),
        ]);
    }

    private static function lunes(string $fecha): CarbonImmutable
    {
        return Semana::fecha($fecha)->startOfWeek();
    }
}
