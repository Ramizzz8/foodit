<?php

use App\Cocina\CalculadoraReceta;
use App\Models\Alimento;
use App\Models\IngredienteReceta;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use App\Models\User;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;

function recetaArrozConHuevo(array $receta = []): Receta
{
    $arroz = Alimento::factory()->create(['kcal' => 368, 'proteinas' => 6.5, 'hidratos' => 79, 'grasas' => 0.6,
        'producto_mercadona_id' => ProductoMercadona::factory()->create(['cantidad_envase' => 1000, 'unidad_envase' => 'g', 'precio' => 1.20])->id]);
    $huevo = Alimento::factory()->create(['kcal' => 143, 'proteinas' => 12.6, 'hidratos' => 0.7, 'grasas' => 9.5, 'gramos_por_unidad' => 60,
        'producto_mercadona_id' => ProductoMercadona::factory()->create(['cantidad_envase' => 12, 'unidad_envase' => 'ud', 'precio' => 2.70])->id]);
    $r = Receta::factory()->create(['raciones' => 2, ...$receta]);
    IngredienteReceta::factory()->for($r)->create(['alimento_id' => $arroz->id, 'cantidad' => 200, 'unidad' => 'g', 'texto' => 'arroz', 'orden' => 1]);
    IngredienteReceta::factory()->for($r)->create(['alimento_id' => $huevo->id, 'cantidad' => 2, 'unidad' => 'ud', 'texto' => 'huevos', 'orden' => 2]);

    return $r;
}

it('calcula coste y kcal por ración', function () {
    $c = app(CalculadoraReceta::class)->porRacion(recetaArrozConHuevo(), null);

    expect($c['coste'])->toBe(0.35)            // (0,24 € + 0,45 €) / 2
        ->and($c['kcal'])->toBe(453.8)         // (736 + 171,6) / 2
        ->and($c['proteinas'])->toBe(14.06)    // (13 + 15,12) / 2
        ->and($c['completo'])->toBeTrue()
        ->and($c['faltan'])->toBe([]);
});

it('dice qué ingrediente falta en vez de contar cero', function () {
    $r = recetaArrozConHuevo();
    $azafran = Alimento::factory()->create(['nombre' => 'azafrán', 'producto_mercadona_id' => null]);
    IngredienteReceta::factory()->for($r)->create(['alimento_id' => $azafran->id, 'cantidad' => 1, 'unidad' => 'pizca', 'texto' => 'una pizca de azafrán']);
    IngredienteReceta::factory()->for($r)->create(['alimento_id' => null, 'cantidad' => 1, 'unidad' => 'ud', 'texto' => 'hoja de lima kaffir']);

    $c = app(CalculadoraReceta::class)->porRacion($r, null);

    expect($c['completo'])->toBeFalse()
        ->and($c['coste'])->toBe(0.35)
        ->and($c['faltan'])->toBe([
            ['texto' => 'una pizca de azafrán', 'motivo' => 'sin_producto'],
            ['texto' => 'hoja de lima kaffir', 'motivo' => 'sin_alimento'],
        ]);
});

it('usa el producto que ha elegido el usuario', function () {
    $r = recetaArrozConHuevo();
    $u = User::factory()->create();
    $caro = ProductoMercadona::factory()->create(['cantidad_envase' => 1000, 'unidad_envase' => 'g', 'precio' => 2.40]);
    $this->actingAs($u)->putJson("/alimentos/{$r->ingredientes[0]->alimento_id}/producto", ['producto_mercadona_id' => $caro->id]);

    expect(app(CalculadoraReceta::class)->porRacion($r->fresh(), $u)['coste'])->toBe(0.47); // (0,48 + 0,45) / 2
});

it('lista las recetas de Foodit y las propias', function () {
    $u = User::factory()->create();
    Receta::factory()->create(['titulo' => 'Lentejas estofadas']);
    Receta::factory()->for($u, 'usuario')->create(['titulo' => 'Mi tortilla']);
    Receta::factory()->for(User::factory(), 'usuario')->create(['titulo' => 'Receta ajena']);

    $this->actingAs($u)->get('/recetas')->assertOk()->assertInertia(fn ($p) => $p
        ->component('recetas/index')
        ->has('recetas.data', 2)
        ->where('recetas.data.0.titulo', fn ($t) => in_array($t, ['Lentejas estofadas', 'Mi tortilla'])));
});

it('filtra por texto y por etiqueta', function () {
    $u = User::factory()->create();
    Receta::factory()->create(['titulo' => 'Lentejas estofadas', 'etiquetas' => ['legumbre']]);
    Receta::factory()->create(['titulo' => 'Ensalada mixta', 'etiquetas' => ['rápida', 'vegetariana']]);

    $this->actingAs($u)->get('/recetas?q=lentej')->assertInertia(fn ($p) => $p->has('recetas.data', 1));
    $this->actingAs($u)->get('/recetas?etiqueta=vegetariana')->assertInertia(fn ($p) => $p->has('recetas.data', 1)->where('recetas.data.0.titulo', 'Ensalada mixta'));
});

it('no deja ver ni editar la receta de otro', function () {
    $ajena = Receta::factory()->for(User::factory(), 'usuario')->create();
    $u = User::factory()->create();

    $this->actingAs($u)->get("/recetas/{$ajena->id}")->assertNotFound();
    $this->actingAs($u)->get("/recetas/{$ajena->id}/editar")->assertNotFound();
    $this->actingAs($u)->put("/recetas/{$ajena->id}", ['titulo' => 'Mía'])->assertNotFound();
    $this->actingAs($u)->delete("/recetas/{$ajena->id}")->assertNotFound();
    expect($ajena->fresh())->not->toBeNull();
});

it('no deja editar una receta de Foodit, pero sí duplicarla', function () {
    $deFoodit = recetaArrozConHuevo(['titulo' => 'Arroz a la cubana']);
    $u = User::factory()->create();

    $this->actingAs($u)->put("/recetas/{$deFoodit->id}", ['titulo' => 'Mío'])->assertForbidden();
    $this->actingAs($u)->post("/recetas/{$deFoodit->id}/duplicar")->assertRedirect();

    $copia = $u->recetas()->first();
    expect($copia->titulo)->toBe('Arroz a la cubana')
        ->and($copia->receta_origen_id)->toBe($deFoodit->id)
        ->and($copia->ingredientes)->toHaveCount(2);
});

it('guarda una receta de Foodit en el recetario', function () {
    $deFoodit = Receta::factory()->create();
    $u = User::factory()->create();

    $this->actingAs($u)->post("/recetas/{$deFoodit->id}/guardar")->assertRedirect();
    expect($u->recetasGuardadas()->pluck('recetas.id')->all())->toBe([$deFoodit->id]);

    $this->actingAs($u)->delete("/recetas/{$deFoodit->id}/guardar")->assertRedirect();
    expect($u->recetasGuardadas()->count())->toBe(0);
});

it('crea una receta propia con ingredientes y pasos', function () {
    $u = User::factory()->create();
    $tomate = Alimento::factory()->create(['nombre' => 'tomate']);

    $this->actingAs($u)->post('/recetas', [
        'titulo' => 'Tostada con tomate',
        'raciones' => 1,
        'minutos' => 5,
        'dificultad' => 'facil',
        'etiquetas' => ['desayuno', 'rápida'],
        'pasos' => ['Tuesta el pan.', 'Ralla el tomate por encima.'],
        'ingredientes' => [
            ['texto' => '1 tomate', 'alimento_id' => $tomate->id, 'cantidad' => 1, 'unidad' => 'ud', 'nota' => 'maduro'],
            ['texto' => 'sal', 'alimento_id' => null, 'cantidad' => null, 'unidad' => 'al_gusto'],
        ],
    ])->assertRedirect();

    $r = $u->recetas()->first();
    expect($r->titulo)->toBe('Tostada con tomate')
        ->and($r->pasos)->toBe(['Tuesta el pan.', 'Ralla el tomate por encima.'])
        ->and($r->ingredientes->pluck('texto')->all())->toBe(['1 tomate', 'sal']);
});

it('valida la receta', function () {
    $this->actingAs(User::factory()->create())
        ->post('/recetas', ['titulo' => '', 'raciones' => 0, 'ingredientes' => [['texto' => 'x', 'unidad' => 'puñado']]])
        ->assertSessionHasErrors(['titulo', 'raciones', 'ingredientes.0.unidad', 'pasos']);
});

it('el seeder crea 42 recetas con todos sus ingredientes calculables', function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    $this->seed(RecetasSeeder::class); // se puede repetir sin duplicar

    $recetas = Receta::whereNull('user_id')->with('ingredientes.alimento')->get();
    // En los tests no hay catálogo de Mercadona: aquí solo puede faltar el producto, nunca el alimento, la cantidad o la conversión.
    $problemas = $recetas->flatMap(fn ($r) => collect(app(CalculadoraReceta::class)->porRacion($r, null)['faltan'])
        ->reject(fn ($f) => $f['motivo'] === 'sin_producto')
        ->map(fn ($f) => "{$r->titulo}: {$f['texto']} ({$f['motivo']})"));

    expect($recetas)->toHaveCount(42)
        ->and($problemas->all())->toBe([])
        ->and($recetas->every(fn ($r) => count($r->pasos) >= 2 && $r->ingredientes->isNotEmpty()))->toBeTrue();
});
