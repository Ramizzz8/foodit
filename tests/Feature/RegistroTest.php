<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('cualquiera puede crear su cuenta y empieza por la bienvenida', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Marta', 'email' => 'marta@ejemplo.es', 'password' => 'una-clave-larga', 'password_confirmation' => 'una-clave-larga',
    ])->assertRedirect('/bienvenida');

    $marta = User::firstWhere('email', 'marta@ejemplo.es');
    expect($marta->es_demo)->toBeFalse()->and($marta->caduca_en)->toBeNull();
    $this->assertAuthenticatedAs($marta);
    Notification::assertSentTo($marta, VerifyEmail::class);
});

it('sin verificar el email se puede usar la app, con un aviso', function () {
    $u = User::factory()->unverified()->create(['perfil_completo_en' => now()]);

    $this->actingAs($u)->get('/plan')->assertOk()
        ->assertInertia(fn ($p) => $p->where('auth.verificado', false));
});

it('los correos de la cuenta van en español', function () {
    $u = User::factory()->unverified()->create();

    $verificar = (new VerifyEmail)->toMail($u);
    expect($verificar->subject)->toBe('Confirma tu email en Foodit')
        ->and(implode(' ', $verificar->introLines))->toContain('Foodit');

    $recuperar = (new ResetPassword('token'))->toMail($u);
    expect($recuperar->subject)->toBe('Cambia tu contraseña de Foodit');
});

it('tiene una página de privacidad', function () {
    $this->get('/privacidad')->assertOk()->assertInertia(fn ($p) => $p->component('privacidad'));
});

it('la portada invita a crear una cuenta', function () {
    $this->get('/')->assertInertia(fn ($p) => $p->where('registro', true));
});
