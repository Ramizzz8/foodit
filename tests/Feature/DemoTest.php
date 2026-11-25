<?php

use App\Demo\CrearCuentaDemo;
use App\Demo\LimpiarCuentasDemo;
use App\Models\ComidaPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;

beforeEach(function () {
    config(['foodit.demo' => true]);
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2026-11-25 12:00'));
});

it('sin modo demo no existe', function () {
    config(['foodit.demo' => false]);

    $this->post('/demo/entrar')->assertNotFound();
    $this->assertGuest();
    expect(User::count())->toBe(0);
});

it('crea una copia con los datos de ejemplo y entra en el plan', function () {
    $this->post('/demo/entrar')->assertRedirect('/plan');

    $u = User::sole();
    $this->assertAuthenticatedAs($u);
    expect($u->es_demo)->toBeTrue()
        ->and($u->caduca_en->toDateTimeString())->toBe('2026-11-26 12:00:00')
        ->and($u->hasVerifiedEmail())->toBeTrue()
        ->and($u->objetivo_kcal)->toBe(1600)
        ->and($u->planifica_desayuno)->toBeTrue()
        ->and($u->recetas()->count())->toBeGreaterThanOrEqual(2)
        ->and($u->recetasGuardadas()->count())->toBeGreaterThanOrEqual(3)
        ->and($u->comidasPlan()->whereBetween('fecha', ['2026-11-23', '2026-11-29'])->count())->toBeGreaterThanOrEqual(19);
});

it('cada visitante recibe su copia', function () {
    $this->post('/demo/entrar');
    $ana = auth()->user();
    $this->flushSession();
    auth()->logout();
    $this->post('/demo/entrar');
    $luis = auth()->user();

    expect($ana->id)->not->toBe($luis->id)
        ->and($ana->comidasPlan()->count())->toBe($luis->comidasPlan()->count());

    // Lo que cambia uno no lo ve el otro.
    $luis->comidasPlan()->delete();
    expect($ana->comidasPlan()->count())->toBeGreaterThan(0);
});

it('vuelve a su copia si ya la tiene', function () {
    $this->post('/demo/entrar');
    $primera = auth()->id();
    auth()->logout();

    $this->post('/demo/entrar');

    expect(auth()->id())->toBe($primera)->and(User::count())->toBe(1);
});

it('limita a 5 copias por hora por IP', function () {
    foreach (range(1, 5) as $_) {
        $this->flushSession();
        $this->post('/demo/entrar')->assertRedirect('/plan');
        auth()->logout();
    }
    $this->flushSession();

    $this->post('/demo/entrar')->assertRedirect()->assertSessionHasErrors('demo');
    expect(User::count())->toBe(5);
});

it('borra las caducadas y respeta el tope de cuentas vivas', function () {
    $normal = User::factory()->create();
    $caducada = app(CrearCuentaDemo::class)->ejecutar('10.0.0.1');
    $this->travel(25)->hours();
    $viva = app(CrearCuentaDemo::class)->ejecutar('10.0.0.2');

    expect(app(LimpiarCuentasDemo::class)->ejecutar(tope: 200))->toBe(1)
        ->and(User::pluck('id')->all())->toEqualCanonicalizing([$normal->id, $viva->id])
        ->and(ComidaPlan::where('user_id', $caducada->id)->count())->toBe(0);

    $otra = app(CrearCuentaDemo::class)->ejecutar('10.0.0.3');
    app(LimpiarCuentasDemo::class)->ejecutar(tope: 1);
    expect(User::pluck('id')->all())->toEqualCanonicalizing([$normal->id, $otra->id]);
});

it('la limpieza se programa cada hora', function () {
    $this->artisan('foodit:limpiar-demo')->assertSuccessful();
});

it('la cuenta demo no cambia email ni contraseña ni se borra', function () {
    $this->post('/demo/entrar');
    $u = auth()->user();
    $email = $u->email;

    $this->patch('/settings/profile', ['name' => 'X', 'email' => 'yo@ejemplo.com'])->assertSessionHasErrors('demo');
    $this->put('/settings/password', ['current_password' => 'x', 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1'])->assertSessionHasErrors('demo');
    $this->delete('/settings/profile', ['password' => 'x'])->assertSessionHasErrors('demo');

    expect($u->fresh()->email)->toBe($email);
});

it('la plantilla cae en la semana actual', function () {
    $this->post('/demo/entrar');
    $enEstaSemana = auth()->user()->comidasPlan()->whereBetween('fecha', ['2026-11-23', '2026-11-29'])->count();
    auth()->logout();
    $this->flushSession();

    $this->travelTo(CarbonImmutable::parse('2027-03-10 09:00'));
    $this->post('/demo/entrar');

    expect(auth()->user()->comidasPlan()->whereBetween('fecha', ['2027-03-08', '2027-03-14'])->count())->toBe($enEstaSemana);
});

it('el login ofrece probar la demo', function () {
    $this->get('/login')->assertInertia(fn ($p) => $p->where('demo', true));
});
