<?php

use App\Models\Alimento;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use Illuminate\Support\Facades\Http;

it('deja la base lista: alimentos, recetas y catálogo si está vacío', function () {
    Http::fake([
        'tienda.mercadona.es/api/categories/112/*' => Http::response(json_decode(file_get_contents(base_path('tests/Fixtures/mercadona/sub112.json')), true)),
        'tienda.mercadona.es/api/categories/77/*' => Http::response(json_decode(file_get_contents(base_path('tests/Fixtures/mercadona/sub77.json')), true)),
        'tienda.mercadona.es/api/categories/27/*' => Http::response(json_decode(file_get_contents(base_path('tests/Fixtures/mercadona/sub27.json')), true)),
        'tienda.mercadona.es/api/categories/*' => Http::response(json_decode(file_get_contents(base_path('tests/Fixtures/mercadona/raiz.json')), true)),
    ]);

    $this->artisan('foodit:preparar')->assertSuccessful();
    $this->artisan('foodit:preparar')->assertSuccessful(); // se puede repetir

    expect(Alimento::count())->toBeGreaterThan(80)
        ->and(Receta::whereNull('user_id')->count())->toBe(42)
        ->and(ProductoMercadona::count())->toBe(9);
    Http::assertSentCount(4); // la segunda vez ya hay catálogo: no lo vuelve a pedir
});
