<?php

use App\Models\User;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;

it('presenta Foodit con un tique de ejemplo hecho con recetas reales', function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    config(['foodit.demo' => true]);

    $this->get('/')->assertOk()->assertInertia(fn ($p) => $p
        ->component('welcome')
        ->where('demo', true)
        ->has('tique', 7)
        ->has('tique.0.opciones', 3)
        ->has('tique.0.opciones.0', fn ($o) => $o->hasAll(['id', 'titulo', 'coste', 'kcal', 'foto'])->etc())
        ->has('productos'));
});

it('a quien ya ha entrado le ofrece ir a su plan', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertInertia(fn ($p) => $p->where('auth.user', fn ($u) => $u !== null));
});
