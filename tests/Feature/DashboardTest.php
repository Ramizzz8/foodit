<?php

use App\Models\User;

it('lleva a los invitados al login', function () {
    $this->get('/plan')->assertRedirect(route('login'));
});

it('el antiguo panel lleva al plan de la semana', function () {
    $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/plan');
});
