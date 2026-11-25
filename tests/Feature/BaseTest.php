<?php

use App\Models\Receta;
use App\Models\User;

it('no deja registrarse si el registro está desactivado', function () {
    expect(config('foodit.registro'))->toBeFalse();

    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::count())->toBe(0);
});

it('usa español y la hora de Madrid', function () {
    expect(app()->getLocale())->toBe('es')
        ->and(config('app.timezone'))->toBe('Europe/Madrid')
        ->and(config('app.name'))->toBe('Foodit')
        ->and(config('foodit.almacen'))->toBe('vlc1');
});

it('una receta de otro usuario no es visible', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $deFoodit = Receta::factory()->create(['user_id' => null]);
    $deA = Receta::factory()->create(['user_id' => $a->id]);
    $deB = Receta::factory()->create(['user_id' => $b->id]);

    $ids = Receta::visiblesPara($a)->pluck('id')->all();

    expect($ids)->toContain($deFoodit->id, $deA->id)->not->toContain($deB->id);
});

it('el usuario tiene objetivos por defecto', function () {
    $u = User::factory()->create()->fresh();

    expect($u->objetivo_kcal)->toBe(2000)
        ->and((float) $u->presupuesto_semanal)->toBe(60.0)
        ->and($u->planifica_desayuno)->toBeFalse()
        ->and($u->es_demo)->toBeFalse();
});
