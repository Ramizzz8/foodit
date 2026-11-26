<?php

use App\Compra\ListaCompra;
use App\Demo\CrearCuentaDemo;
use App\Models\Alimento;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;

beforeEach(function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    Alimento::whereNotNull('producto_mercadona_id')->get()->each(fn (Alimento $a) => ProductoMercadona::factory()->create([
        'id' => $a->producto_mercadona_id, 'cantidad_envase' => 1000, 'unidad_envase' => 'g', 'precio' => 2,
    ]));
});

function alimento(string $nombre): Alimento
{
    return Alimento::where('nombre', $nombre)->firstOrFail();
}

it('se añaden y se quitan alimentos de la despensa', function () {
    $u = User::factory()->create();

    $this->actingAs($u)->post('/despensa', ['alimento_id' => alimento('arroz redondo')->id])->assertRedirect();
    $this->actingAs($u)->post('/despensa', ['alimento_id' => alimento('arroz redondo')->id])->assertRedirect();
    $this->actingAs($u)->post('/despensa', ['alimento_id' => 999999])->assertSessionHasErrors('alimento_id');
    expect($u->despensa()->pluck('nombre')->all())->toBe(['arroz redondo']);

    $this->actingAs($u)->get('/despensa')->assertOk()
        ->assertInertia(fn ($p) => $p->component('despensa')->has('despensa', 1)->where('despensa.0.nombre', 'arroz redondo'));

    $this->actingAs($u)->delete('/despensa/'.alimento('arroz redondo')->id)->assertRedirect();
    expect($u->despensa()->count())->toBe(0);
});

it('cada uno ve solo su despensa', function () {
    $ana = User::factory()->create();
    $bea = User::factory()->create();
    $ana->despensa()->attach(alimento('huevo')->id);

    $this->actingAs($bea)->get('/despensa')->assertInertia(fn ($p) => $p->has('despensa', 0));
    $this->actingAs($bea)->delete('/despensa/'.alimento('huevo')->id);
    expect($ana->despensa()->count())->toBe(1);
});

it('marca en la lista lo que quizá ya tienes', function () {
    $u = User::factory()->create();
    $tortilla = Receta::firstWhere('slug', 'tortilla-de-patatas');
    $u->comidasPlan()->create(['fecha' => '2026-11-30', 'momento' => 'cena', 'receta_id' => $tortilla->id]);
    $u->despensa()->attach(alimento('huevo')->id);

    $lista = app(ListaCompra::class)->para($u, CarbonImmutable::parse('2026-11-30'));
    $lineas = collect($lista['grupos'])->flatMap(fn ($g) => $g['lineas'])->keyBy('alimento');

    expect($lineas['huevo']['quiza_en_despensa'])->toBeTrue()
        ->and($lineas['patata']['quiza_en_despensa'])->toBeFalse();
});

it('con lo que tienes, ordena las recetas por lo que te falta', function () {
    $u = User::factory()->create();
    foreach (['huevo', 'patata', 'aceite de oliva virgen extra', 'cebolla'] as $n) {
        $u->despensa()->attach(alimento($n)->id);
    }

    $this->actingAs($u)->get('/despensa')->assertInertia(fn ($p) => $p
        ->where('sugerencias.0.titulo', 'Tortilla de patatas')
        ->where('sugerencias.0.faltan', [])
        ->has('sugerencias.0.tienes'));
});

it('la cuenta de ejemplo trae la despensa llena', function () {
    $demo = app(CrearCuentaDemo::class)->ejecutar('127.0.0.1');

    expect($demo->despensa()->count())->toBe(10);
});
