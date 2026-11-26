<?php

use App\Demo\CrearCuentaDemo;
use App\Models\Alimento;
use App\Models\User;
use App\Perfil\Calorias;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;

function nueva(): User
{
    return User::factory()->sinPerfil()->create();
}

it('calcula las calorías con Mifflin-St Jeor, redondeadas a 50', function () {
    expect(Calorias::objetivo('mujer', 30, 165, 60, 'moderada', 'mantener'))->toBe(2050)
        ->and(Calorias::objetivo('hombre', 40, 180, 85, 'sedentaria', 'perder'))->toBe(1800)
        ->and(Calorias::objetivo('mujer', 80, 145, 38, 'sedentaria', 'perder'))->toBe(1200);
});

it('quien no ha completado el perfil va a la bienvenida', function () {
    $u = nueva();

    $this->actingAs($u)->get('/plan')->assertRedirect('/bienvenida/1');
    $this->actingAs($u)->get('/recetas')->assertRedirect('/bienvenida/1');
    $this->actingAs($u)->get('/bienvenida')->assertRedirect('/bienvenida/1');
    $this->actingAs($u)->get('/bienvenida/1')->assertOk()->assertInertia(fn ($p) => $p->component('bienvenida')->where('paso', 1));
    $this->actingAs($u)->get('/settings/profile')->assertOk();
    $this->actingAs($u)->get('/bienvenida/5')->assertNotFound();
});

it('paso 1: guarda sus datos y sus calorías, solo con consentimiento', function () {
    $u = nueva();
    $datos = ['sexo' => 'mujer', 'edad' => 30, 'altura_cm' => 165, 'peso_kg' => 60, 'actividad' => 'moderada', 'meta' => 'mantener'];

    $this->actingAs($u)->putJson('/bienvenida/1', $datos)->assertUnprocessable()->assertJsonValidationErrors('consentimiento_salud');
    expect($u->fresh()->peso_kg)->toBeNull();

    $this->actingAs($u)->put('/bienvenida/1', [...$datos, 'consentimiento_salud' => true])->assertRedirect('/bienvenida/2');
    $u->refresh();
    expect($u->objetivo_kcal)->toBe(2050)
        ->and((float) $u->peso_kg)->toBe(60.0)
        ->and($u->consentimiento_salud_en)->not->toBeNull();

    // Puede cambiar la cifra que propone Foodit.
    $this->actingAs($u)->put('/bienvenida/1', [...$datos, 'objetivo_kcal' => 1800])->assertRedirect('/bienvenida/2');
    expect($u->fresh()->objetivo_kcal)->toBe(1800);
});

it('paso 2: alergias, dieta y alimentos vetados, solo con consentimiento', function () {
    $this->seed(AlimentosSeeder::class);
    $u = nueva();
    $pepino = Alimento::where('nombre', 'pepino')->value('id');
    $datos = ['alergenos' => ['lacteos', 'gluten'], 'dieta' => 'vegetariana', 'alimentos_vetados' => [$pepino]];

    $this->actingAs($u)->putJson('/bienvenida/2', $datos)->assertUnprocessable();
    expect($u->fresh()->alergenos)->toBeNull();

    $this->actingAs($u)->putJson('/bienvenida/2', [...$datos, 'alergenos' => ['chocolate'], 'consentimiento_salud' => true])->assertJsonValidationErrors('alergenos.0');

    $this->actingAs($u)->put('/bienvenida/2', [...$datos, 'consentimiento_salud' => true])->assertRedirect('/bienvenida/3');
    $u->refresh();
    expect($u->alergenos)->toBe(['lacteos', 'gluten'])->and($u->dieta)->toBe('vegetariana')->and($u->alimentos_vetados)->toBe([$pepino]);
});

it('paso 3 y 4: su semana y empezar', function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    $u = nueva();

    $this->actingAs($u)->put('/bienvenida/3', ['presupuesto_semanal' => 45, 'planifica_desayuno' => true, 'minutos_entre_semana' => 20])->assertRedirect('/bienvenida/4');
    $u->refresh();
    expect((float) $u->presupuesto_semanal)->toBe(45.0)->and($u->planifica_desayuno)->toBeTrue()->and($u->minutos_entre_semana)->toBe(20);

    $this->actingAs($u)->put('/bienvenida/4')->assertRedirect('/plan');
    expect($u->fresh()->perfil_completo_en)->not->toBeNull();
    $this->actingAs($u)->get('/plan')->assertOk();
});

it('las cuentas de ejemplo no pasan por la bienvenida', function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    $demo = app(CrearCuentaDemo::class)->ejecutar('127.0.0.1');

    $this->actingAs($demo)->get('/plan')->assertOk();
});
