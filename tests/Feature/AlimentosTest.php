<?php

use App\Cocina\BuscadorAlimentos;
use App\Models\Alimento;
use App\Models\ProductoMercadona;
use App\Models\User;
use Database\Seeders\AlimentosSeeder;

function alimento(string $nombre, array $extra = []): Alimento
{
    return Alimento::factory()->create(['nombre' => $nombre, 'slug' => Str::slug($nombre), ...$extra]);
}

it('encuentra el alimento aunque el texto venga con detalles', function () {
    foreach (['tomate', 'cebolla', 'pimiento rojo', 'pimiento verde', 'aceite de oliva virgen extra', 'huevo'] as $n) {
        alimento($n);
    }
    $b = app(BuscadorAlimentos::class);

    expect($b->mejor('2 tomates maduros')?->nombre)->toBe('tomate')
        ->and($b->mejor('Cebolla picada')?->nombre)->toBe('cebolla')
        ->and($b->mejor('pimientos rojos')?->nombre)->toBe('pimiento rojo')
        ->and($b->mejor('un chorrito de aceite de oliva')?->nombre)->toBe('aceite de oliva virgen extra')
        ->and($b->mejor('3 huevos')?->nombre)->toBe('huevo')
        ->and($b->mejor('xyzzy'))->toBeNull();
});

it('busca alimentos para el formulario de recetas', function () {
    alimento('arroz redondo');
    alimento('arroz largo');
    alimento('tomate');

    $this->actingAs(User::factory()->create())
        ->getJson('/alimentos/buscar?q=arroz')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.nombre', fn ($n) => str_starts_with($n, 'arroz'));
});

it('cada usuario puede cambiar el producto de un alimento solo para él', function () {
    $porDefecto = ProductoMercadona::factory()->create();
    $otro = ProductoMercadona::factory()->create();
    $arroz = alimento('arroz', ['producto_mercadona_id' => $porDefecto->id]);
    [$ana, $luis] = User::factory()->count(2)->create();

    $this->actingAs($ana)->putJson("/alimentos/{$arroz->id}/producto", ['producto_mercadona_id' => $otro->id])->assertOk();

    expect($arroz->productoPara($ana)->id)->toBe($otro->id)
        ->and($arroz->productoPara($luis)->id)->toBe($porDefecto->id)
        ->and($arroz->productoPara(null)->id)->toBe($porDefecto->id);
});

it('no deja elegir un producto que no existe', function () {
    $arroz = alimento('arroz');

    $this->actingAs(User::factory()->create())
        ->putJson("/alimentos/{$arroz->id}/producto", ['producto_mercadona_id' => 'no-existe'])
        ->assertUnprocessable();
});

it('sugiere productos de Mercadona por nombre', function () {
    ProductoMercadona::factory()->create(['nombre' => 'Arroz redondo Hacendado']);
    ProductoMercadona::factory()->create(['nombre' => 'Tomate frito Hacendado']);
    $arroz = alimento('arroz redondo');

    $this->actingAs(User::factory()->create())
        ->getJson("/alimentos/{$arroz->id}/productos")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.nombre', 'Arroz redondo Hacendado');
});

it('el seeder deja todos los alimentos con kcal, fuente y producto', function () {
    $this->seed(AlimentosSeeder::class);

    expect(Alimento::count())->toBeGreaterThan(80)
        ->and(Alimento::whereNull('kcal')->count())->toBe(0)
        ->and(Alimento::whereNull('producto_mercadona_id')->count())->toBe(0)
        ->and(Alimento::where('fuente', 'not like', 'usda:%')->where('fuente', 'not like', 'off:%')->count())->toBe(0);
});

it('el seeder se puede repetir sin duplicar', function () {
    $this->seed(AlimentosSeeder::class);
    $n = Alimento::count();
    $this->seed(AlimentosSeeder::class);

    expect(Alimento::count())->toBe($n);
});
