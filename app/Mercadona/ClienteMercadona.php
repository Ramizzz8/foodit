<?php

namespace App\Mercadona;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** API pública (no oficial) de la tienda online de Mercadona. */
class ClienteMercadona
{
    private const BASE = 'https://tienda.mercadona.es/api';

    /** @return list<array{id: int, name: string, order: int, categories: list<array{id: int, name: string}>}> categorías raíz */
    public function categorias(): array
    {
        return $this->pedir('/categories/', 'results');
    }

    /** @return list<array{id: int, name: string, products: list<array<string, mixed>>}> secciones con sus productos */
    public function subcategoria(int $id): array
    {
        return $this->pedir("/categories/{$id}/", 'categories');
    }

    private function pedir(string $ruta, string $clave): array
    {
        $respuesta = $this->http()->get(self::BASE.$ruta, ['lang' => 'es', 'wh' => config('foodit.almacen')]);

        $datos = $respuesta->successful() ? $respuesta->json() : null;
        if (! is_array($datos) || ! is_array($datos[$clave] ?? null)) {
            throw new RuntimeException("Respuesta inesperada de Mercadona en {$ruta} (HTTP {$respuesta->status()}).");
        }

        return $datos[$clave];
    }

    private function http(): PendingRequest
    {
        return Http::withUserAgent('Foodit/1.0')
            ->acceptJson()
            ->timeout(20)
            ->retry(3, app()->runningUnitTests() ? 0 : 2000, throw: false);
    }
}
