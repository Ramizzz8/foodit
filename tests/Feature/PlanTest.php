<?php

use App\Models\Alimento;
use App\Models\ComidaPlan;
use App\Models\IngredienteReceta;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use App\Models\User;
use App\Plan\ResumenSemana;
use Carbon\CarbonImmutable;

/** Receta de 1 ración con las kcal y el coste exactos que se pidan (alimento de 100 kcal/100 g a 1 €/kg). */
function recetaDe(int $kcal, array $extra = []): Receta
{
    $a = Alimento::factory()->create(['kcal' => 100, 'producto_mercadona_id' => ProductoMercadona::factory()->create(['cantidad_envase' => 1000, 'unidad_envase' => 'g', 'precio' => 1.0])->id]);
    $r = Receta::factory()->create(['raciones' => 1, ...$extra]);
    IngredienteReceta::factory()->for($r)->create(['alimento_id' => $a->id, 'cantidad' => $kcal, 'unidad' => 'g']);

    return $r;
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 12:00')); // miércoles
    $this->lunes = CarbonImmutable::parse('2026-11-23');
});

it('suma el día y lo compara con el objetivo', function () {
    $u = User::factory()->create(['objetivo_kcal' => 2000]);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-23', 'momento' => 'comida', 'receta_id' => recetaDe(900)->id]);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-23', 'momento' => 'cena', 'receta_id' => recetaDe(1150)->id]);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-24', 'momento' => 'comida', 'receta_id' => recetaDe(900)->id]);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-25', 'momento' => 'comida', 'receta_id' => recetaDe(1200)->id, 'raciones' => 2]);

    $dias = collect(app(ResumenSemana::class)->para($u, $this->lunes)['dias'])->keyBy('fecha');

    expect($dias['2026-11-23'])->kcal->toBe(2050.0)->estado->toBe('dentro')
        ->and($dias['2026-11-24'])->estado->toBe('debajo')
        ->and($dias['2026-11-25'])->kcal->toBe(2400.0)->estado->toBe('encima')
        ->and($dias['2026-11-26'])->estado->toBe('vacio')
        ->and($dias)->toHaveCount(7);
});

it('el coste de la semana es lo que se come', function () {
    $u = User::factory()->create(['presupuesto_semanal' => 60]);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-23', 'momento' => 'comida', 'receta_id' => recetaDe(500)->id, 'raciones' => 2]); // 2 × 0,50 €
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-24', 'momento' => 'cena', 'tipo' => 'fuera', 'receta_id' => null]);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-30', 'momento' => 'cena', 'receta_id' => recetaDe(900)->id]); // otra semana

    $r = app(ResumenSemana::class)->para($u, $this->lunes);

    expect($r['coste'])->toBe(1.0)->and($r['presupuesto'])->toBe(60.0)->and($r['completo'])->toBeTrue();
});

it('muestra la semana con sus comidas y las recetas para elegir', function () {
    $u = User::factory()->create();
    $r = recetaDe(600, ['titulo' => 'Lentejas']);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-24', 'momento' => 'cena', 'receta_id' => $r->id]);

    $this->actingAs($u)->get('/plan')->assertOk()->assertInertia(fn ($p) => $p
        ->component('plan/semana')
        ->where('lunes', '2026-11-23')
        ->where('momentos', ['comida', 'cena'])
        ->where('comidas.2026-11-24|cena.receta.titulo', 'Lentejas')
        ->has('recetas', 1)
        ->has('resumen.dias', 7));
});

it('incluye el desayuno si el usuario lo planifica', function () {
    $u = User::factory()->create(['planifica_desayuno' => true]);

    $this->actingAs($u)->get('/plan')->assertInertia(fn ($p) => $p->where('momentos', ['desayuno', 'comida', 'cena']));
});

it('una fecha que no es lunes lleva a su lunes', function () {
    $this->actingAs(User::factory()->create())->get('/plan/2026-11-26')->assertRedirect('/plan/2026-11-23');
    $this->actingAs(User::factory()->create())->get('/plan/no-es-fecha')->assertNotFound();
});

it('pone, cambia y quita una comida', function () {
    $u = User::factory()->create();
    $r = recetaDe(600);

    $this->actingAs($u)->put('/plan/2026-11-24/cena', ['tipo' => 'receta', 'receta_id' => $r->id, 'raciones' => 1])->assertRedirect();
    $this->actingAs($u)->put('/plan/2026-11-24/cena', ['tipo' => 'receta', 'receta_id' => $r->id, 'raciones' => 2])->assertRedirect();
    expect($u->comidasPlan()->sole())->raciones->toBe(2.0);

    $this->actingAs($u)->put('/plan/2026-11-24/cena', ['tipo' => 'fuera'])->assertRedirect();
    expect($u->comidasPlan()->sole())->tipo->toBe('fuera')->receta_id->toBeNull();

    $this->actingAs($u)->delete('/plan/2026-11-24/cena')->assertRedirect();
    expect($u->comidasPlan()->count())->toBe(0);
});

it('no deja poner en el plan una receta privada de otro', function () {
    $ajena = recetaDe(600, ['user_id' => User::factory()->create()->id]);
    $u = User::factory()->create();

    $this->actingAs($u)->put('/plan/2026-11-24/cena', ['tipo' => 'receta', 'receta_id' => $ajena->id])->assertSessionHasErrors('receta_id');
    expect(ComidaPlan::count())->toBe(0);
});

it('cada usuario solo toca su propio plan', function () {
    $ana = User::factory()->create();
    $luis = User::factory()->create();
    ComidaPlan::factory()->for($ana)->create(['fecha' => '2026-11-24', 'momento' => 'cena']);

    $this->actingAs($luis)->delete('/plan/2026-11-24/cena');

    expect($ana->comidasPlan()->count())->toBe(1);
});

it('rechaza un momento o una fecha que no existen', function () {
    $u = User::factory()->create();

    $this->actingAs($u)->put('/plan/2026-11-24/merienda', ['tipo' => 'fuera'])->assertNotFound();
    $this->actingAs($u)->put('/plan/2026-02-30/cena', ['tipo' => 'fuera'])->assertNotFound();
});

it('copia la semana anterior sin pisar lo que ya hay', function () {
    $u = User::factory()->create();
    $a = recetaDe(500);
    $b = recetaDe(700);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-16', 'momento' => 'comida', 'receta_id' => $a->id]);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-17', 'momento' => 'cena', 'receta_id' => $a->id]);
    ComidaPlan::factory()->for($u)->create(['fecha' => '2026-11-24', 'momento' => 'cena', 'receta_id' => $b->id]);

    $this->actingAs($u)->post('/plan/2026-11-23/copiar-anterior')->assertRedirect();

    $semana = $u->comidasPlan()->whereBetween('fecha', ['2026-11-23', '2026-11-29'])->get()->keyBy(fn ($c) => $c->fecha->toDateString().'|'.$c->momento);
    expect($semana)->toHaveCount(2)
        ->and($semana['2026-11-23|comida']->receta_id)->toBe($a->id)
        ->and($semana['2026-11-24|cena']->receta_id)->toBe($b->id);
});

it('guarda los objetivos', function () {
    $u = User::factory()->create();

    $this->actingAs($u)->put('/ajustes/objetivos', ['objetivo_kcal' => 1800, 'presupuesto_semanal' => 45.5, 'planifica_desayuno' => true])->assertRedirect();
    expect($u->fresh())->objetivo_kcal->toBe(1800)->presupuesto_semanal->toBe('45.50')->planifica_desayuno->toBeTrue();

    $this->actingAs($u)->put('/ajustes/objetivos', ['objetivo_kcal' => 200, 'presupuesto_semanal' => 5000])->assertSessionHasErrors(['objetivo_kcal', 'presupuesto_semanal']);
});
