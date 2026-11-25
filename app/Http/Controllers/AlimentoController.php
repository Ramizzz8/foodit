<?php

namespace App\Http\Controllers;

use App\Cocina\BuscadorAlimentos;
use App\Models\Alimento;
use App\Models\AlimentoProductoUsuario;
use App\Models\ProductoMercadona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlimentoController extends Controller
{
    /** Buscador de alimentos del formulario de recetas. */
    public function buscar(Request $request, BuscadorAlimentos $buscador): JsonResponse
    {
        return response()->json(
            $buscador->buscar((string) $request->query('q', ''))->map(fn (Alimento $a) => ['id' => $a->id, 'nombre' => $a->nombre])
        );
    }

    /** Productos de Mercadona que encajan con el nombre del alimento (o con lo que escriba el usuario). */
    public function productos(Request $request, Alimento $alimento): JsonResponse
    {
        $palabras = BuscadorAlimentos::palabras((string) $request->query('q', $alimento->nombre));

        $productos = ProductoMercadona::query()
            ->where(function ($q) use ($palabras) {
                foreach ($palabras as $p) {
                    $q->whereRaw('lower(nombre) like ?', ['%'.mb_substr($p, 0, max(4, mb_strlen($p) - 1)).'%']);
                }
            })
            ->orderBy('precio_referencia')
            ->limit(12)
            ->get(['id', 'nombre', 'envase', 'cantidad_envase', 'unidad_envase', 'precio', 'foto_url']);

        return response()->json($productos);
    }

    /** El usuario elige otro producto para un alimento; solo cambia para él. */
    public function elegirProducto(Request $request, Alimento $alimento): JsonResponse|RedirectResponse
    {
        $datos = $request->validate(['producto_mercadona_id' => ['required', 'string', 'exists:productos_mercadona,id']]);

        AlimentoProductoUsuario::updateOrCreate(
            ['user_id' => $request->user()->id, 'alimento_id' => $alimento->id],
            ['producto_mercadona_id' => $datos['producto_mercadona_id']],
        );

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('exito', "Producto cambiado para «{$alimento->nombre}».");
    }
}
