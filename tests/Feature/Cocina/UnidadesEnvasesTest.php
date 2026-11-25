<?php

use App\Cocina\Envases;
use App\Cocina\Unidades;
use App\Models\Alimento;
use App\Models\ProductoMercadona;

it('pasa unidades a gramos', function () {
    $huevo = Alimento::factory()->make(['gramos_por_unidad' => 60]);
    $aceite = Alimento::factory()->make(['gramos_por_ml' => 0.92]);

    expect(Unidades::aGramos(2, 'ud', $huevo))->toBe(120.0)
        ->and(Unidades::aGramos(1, 'cda', $aceite))->toBe(13.8)
        ->and(Unidades::aGramos(1, 'l', $aceite))->toBe(920.0)
        ->and(Unidades::aGramos(200, 'ml', Alimento::factory()->make(['gramos_por_ml' => null])))->toBe(200.0)
        ->and(Unidades::aGramos(0.5, 'kg', $huevo))->toBe(500.0)
        ->and(Unidades::aGramos(1, 'pizca', $huevo))->toBe(0.5)
        ->and(Unidades::aGramos(1, 'ud', Alimento::factory()->make(['gramos_por_unidad' => null])))->toBeNull()
        ->and(Unidades::aGramos(null, 'al_gusto', $huevo))->toBe(0.0)
        ->and(Unidades::aGramos(null, 'g', $huevo))->toBeNull();
});

it('redondea hacia arriba a envases y calcula el sobrante', function () {
    $arroz = Alimento::factory()->make();
    $p = ProductoMercadona::factory()->make(['cantidad_envase' => 1000, 'unidad_envase' => 'g', 'precio' => 1.20]);

    expect(Envases::calcular(1200, $p, $arroz))->toMatchArray([
        'envases' => 2, 'comprado' => 2000.0, 'sobra' => 800.0, 'unidad' => 'g', 'coste_compra' => 2.40, 'coste_usado' => 1.44,
    ]);
});

it('no compra un envase de más por decimales', function () {
    $p = ProductoMercadona::factory()->make(['cantidad_envase' => 500, 'unidad_envase' => 'g', 'precio' => 1.00]);

    expect(Envases::calcular(1000.0000001, $p, Alimento::factory()->make())['envases'])->toBe(2);
});

it('cuenta huevos por docena', function () {
    $huevo = Alimento::factory()->make(['gramos_por_unidad' => 60]);
    $docena = ProductoMercadona::factory()->make(['cantidad_envase' => 12, 'unidad_envase' => 'ud', 'precio' => 2.70]);

    // 300 g / 60 g = 5 huevos → 1 docena, sobran 7.
    expect(Envases::calcular(300, $docena, $huevo))->toMatchArray(['envases' => 1, 'comprado' => 12.0, 'sobra' => 7.0, 'unidad' => 'ud']);
});

it('pasa de gramos a mililitros con la densidad', function () {
    $aceite = Alimento::factory()->make(['gramos_por_ml' => 0.92]);
    $botella = ProductoMercadona::factory()->make(['cantidad_envase' => 1000, 'unidad_envase' => 'ml', 'precio' => 4.60]);

    // 92 g de aceite = 100 ml.
    expect(Envases::calcular(92, $botella, $aceite))->toMatchArray(['envases' => 1, 'sobra' => 900.0, 'coste_usado' => 0.46]);
});

it('no inventa envases si no puede convertir', function () {
    $sinPeso = Alimento::factory()->make(['gramos_por_unidad' => null]);
    $porUnidades = ProductoMercadona::factory()->make(['unidad_envase' => 'ud', 'cantidad_envase' => 6]);

    expect(Envases::calcular(300, $porUnidades, $sinPeso))->toBeNull();
});
