<?php

namespace App\Mercadona;

use App\Models\PrecioHistorico;
use App\Models\ProductoMercadona;
use App\Models\Sincronizacion;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Copia el catálogo de alimentación de Mercadona a nuestra base de datos.
 * Primero lo descarga todo; solo si todo ha llegado bien escribe, para no dejar el catálogo a medias.
 */
class SincronizarCatalogo
{
    /** Categorías raíz de alimentación (fuera bodega, cuidado personal, bebé, mascotas, limpieza…). */
    public const CATEGORIAS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];

    public function __construct(private ClienteMercadona $cliente) {}

    public function ejecutar(): Sincronizacion
    {
        $s = Sincronizacion::create(['empezada_en' => now(), 'estado' => 'en_curso']);

        try {
            $productos = $this->descargar();
            $this->guardar($productos);
            $s->update(['estado' => 'ok', 'productos' => count($productos), 'terminada_en' => now()]);
        } catch (Throwable $e) {
            report($e);
            $s->update(['estado' => 'error', 'error' => $e->getMessage(), 'terminada_en' => now()]);
        }

        return $s;
    }

    /** @return array<string, array<string, mixed>> filas de productos_mercadona por id */
    private function descargar(): array
    {
        $productos = [];
        foreach ($this->cliente->categorias() as $raiz) {
            if (! in_array($raiz['id'], self::CATEGORIAS, true)) {
                continue;
            }
            foreach ($raiz['categories'] as $sub) {
                $this->pausa();
                foreach ($this->cliente->subcategoria($sub['id']) as $seccion) {
                    foreach ($seccion['products'] ?? [] as $p) {
                        $fila = $this->fila($p, $raiz, $seccion['name']);
                        if ($fila !== null) {
                            $productos[$fila['id']] = $fila;
                        }
                    }
                }
            }
        }

        return $productos;
    }

    /** @return array<string, mixed>|null null si el producto no trae tamaño o precio */
    private function fila(array $p, array $raiz, string $seccion): ?array
    {
        $pi = $p['price_instructions'] ?? [];
        $tamano = (float) ($pi['unit_size'] ?? 0);
        $precio = (float) trim((string) ($pi['unit_price'] ?? ''));
        if ($tamano <= 0 || $precio <= 0) {
            return null;
        }

        [$cantidad, $unidad] = match (true) {
            (bool) ($pi['is_pack'] ?? false) => [(float) ($pi['total_units'] ?? $pi['pack_size'] ?? 1), 'ud'],
            ($pi['size_format'] ?? null) === 'kg' => [$tamano * 1000, 'g'],
            ($pi['size_format'] ?? null) === 'g' => [$tamano, 'g'],
            ($pi['size_format'] ?? null) === 'l' => [$tamano * 1000, 'ml'],
            ($pi['size_format'] ?? null) === 'ml' => [$tamano, 'ml'],
            default => [$tamano, 'ud'],
        };

        return [
            'id' => (string) $p['id'],
            'nombre' => $p['display_name'],
            'envase' => $p['packaging'] ?? null,
            'cantidad_envase' => round($cantidad, 3),
            'unidad_envase' => $unidad,
            'precio' => $precio,
            'precio_referencia' => isset($pi['reference_price']) ? (float) $pi['reference_price'] : null,
            'formato_referencia' => $pi['reference_format'] ?? null,
            'aproximado' => (bool) ($pi['approx_size'] ?? false),
            'categoria' => $raiz['name'],
            'subcategoria' => $seccion,
            'orden_pasillo' => (int) ($raiz['order'] ?? 0),
            'foto_url' => $p['thumbnail'] ?? null,
        ];
    }

    private function guardar(array $productos): void
    {
        $hoy = now()->toDateString();
        $precios = ProductoMercadona::pluck('precio', 'id');

        DB::transaction(function () use ($productos, $hoy, $precios) {
            foreach ($productos as $id => $fila) {
                ProductoMercadona::updateOrCreate(['id' => $id], [...$fila, 'visto_en' => now()]);
                $anterior = $precios[$id] ?? null;
                if ($anterior === null || abs((float) $anterior - $fila['precio']) > 0.001) {
                    PrecioHistorico::create(['producto_mercadona_id' => $id, 'precio' => $fila['precio'], 'fecha' => $hoy]);
                }
            }
        });
    }

    private function pausa(): void
    {
        if (! app()->runningUnitTests()) {
            usleep(300_000);
        }
    }
}
