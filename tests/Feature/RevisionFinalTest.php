<?php

use App\Demo\LimpiarCuentasDemo;
use App\Models\Alimento;
use App\Models\ComidaPlan;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use App\Models\User;
use App\Plan\GenerarSemana;
use Carbon\CarbonImmutable;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    Alimento::whereNotNull('producto_mercadona_id')->get()->each(fn (Alimento $a) => ProductoMercadona::factory()->create([
        'id' => $a->producto_mercadona_id, 'cantidad_envase' => 1000, 'unidad_envase' => 'g', 'precio' => 1 + (crc32($a->nombre) % 500) / 100,
    ]));
});

/** Una receta propia con un ingrediente escrito a mano, sin enlazar a ningún alimento. */
function recetaConNuecesSinEnlazar(User $u): Receta
{
    $r = Receta::create(['user_id' => $u->id, 'titulo' => 'Pollo con salsa de nueces', 'slug' => 'pollo-nueces', 'raciones' => 1, 'minutos' => 15, 'dificultad' => 'facil', 'etiquetas' => [], 'pasos' => ['Cocinar']]);
    $r->ingredientes()->create(['texto' => '250 g de pechuga', 'alimento_id' => Alimento::where('nombre', 'pechuga de pollo')->value('id'), 'cantidad' => 250, 'unidad' => 'g', 'orden' => 0]);
    $r->ingredientes()->create(['texto' => '60 g de nueces', 'alimento_id' => null, 'cantidad' => 60, 'unidad' => 'g', 'orden' => 1]);

    return $r->load('ingredientes.alimento');
}

it('con alergias, una receta con ingredientes sin identificar nunca entra en la semana', function () {
    $u = User::factory()->create(['alergenos' => ['frutos_cascara']]);
    $r = recetaConNuecesSinEnlazar($u);

    expect($r->compatibleCon($u))->toBeFalse();
    foreach (range(1, 15) as $semilla) {
        $s = app(GenerarSemana::class)->para($u, CarbonImmutable::parse('2026-11-30'), $semilla);
        expect(collect($s['comidas'])->pluck('receta_id'))->not->toContain($r->id);
    }

    // Sin restricciones sí se puede proponer.
    expect($r->compatibleCon(User::factory()->create()))->toBeTrue();
});

it('ajustar al presupuesto no hunde las calorías del día', function () {
    $u = User::factory()->create(['objetivo_kcal' => 2000, 'planifica_desayuno' => true, 'presupuesto_semanal' => 10]);

    foreach (range(1, 12) as $semilla) {
        $s = app(GenerarSemana::class)->para($u, CarbonImmutable::parse('2026-11-30'), $semilla);
        foreach (collect($s['comidas'])->groupBy('fecha') as $fecha => $dia) {
            expect($dia->sum('kcal'))->toBeGreaterThanOrEqual(2000 * 0.85, "{$fecha} semilla {$semilla}")->toBeLessThanOrEqual(2000 * 1.15);
        }
        expect($s['aviso'])->toContain('presupuesto');
    }
});

it('una propuesta vacía no borra la semana que ya tenías', function () {
    $u = User::factory()->create(['alimentos_vetados' => Alimento::pluck('id')->all()]);
    $u->comidasPlan()->create(['fecha' => '2026-12-01', 'momento' => 'comida', 'receta_id' => Receta::first()->id]);

    $this->actingAs($u)->post('/plan/2026-11-30/generar', ['semilla' => 1])->assertRedirect()->assertSessionHas('aviso');

    expect(ComidaPlan::where('user_id', $u->id)->count())->toBe(1);
});

it('desde ajustes se ven, se cambian y se borran los datos de salud', function () {
    $u = User::factory()->create([
        'sexo' => 'mujer', 'edad' => 30, 'altura_cm' => 165, 'peso_kg' => 60, 'actividad' => 'moderada', 'meta' => 'mantener',
        'alergenos' => ['gluten'], 'dieta' => 'vegetariana', 'alimentos_vetados' => [1], 'consentimiento_salud_en' => now(),
    ]);

    $this->actingAs($u)->get('/ajustes/salud')->assertOk()
        ->assertInertia(fn ($p) => $p->component('ajustes/salud')->where('salud.alergenos', ['gluten'])->where('salud.peso_kg', 60));

    // Cambiar un paso desde ajustes vuelve a ajustes, no sigue con el alta.
    $this->actingAs($u)->put('/bienvenida/2?editar=1', ['alergenos' => ['gluten', 'lacteos'], 'dieta' => 'omnivora', 'alimentos_vetados' => []])
        ->assertRedirect('/ajustes/salud');
    expect($u->fresh()->alergenos)->toBe(['gluten', 'lacteos']);

    $this->actingAs($u)->delete('/ajustes/salud')->assertRedirect('/ajustes/salud');
    $u->refresh();
    foreach (['sexo', 'edad', 'altura_cm', 'peso_kg', 'actividad', 'meta', 'alergenos', 'alimentos_vetados', 'consentimiento_salud_en'] as $campo) {
        expect($u->{$campo})->toBeNull($campo);
    }
    expect($u->dieta)->toBe('omnivora')->and($u->perfil_completo_en)->not->toBeNull();
});

it('las fotos de las recetas se borran del disco con la receta, al cambiarlas y con la cuenta', function () {
    Storage::fake('public');
    $u = User::factory()->create();
    $datos = fn ($foto) => [
        'titulo' => 'Mi tortilla', 'raciones' => 2, 'dificultad' => 'facil', 'pasos' => ['Batir'],
        'ingredientes' => [['texto' => '4 huevos', 'alimento_id' => null, 'cantidad' => 4, 'unidad' => 'ud']], 'foto' => $foto,
    ];

    $this->actingAs($u)->post('/recetas', $datos(UploadedFile::fake()->image('a.jpg')));
    $r = Receta::where('user_id', $u->id)->latest('id')->first();
    $primera = $r->foto_path;
    Storage::disk('public')->assertExists($primera);

    $this->actingAs($u)->post("/recetas/{$r->id}", [...$datos(UploadedFile::fake()->image('b.jpg')), '_method' => 'put']);
    Storage::disk('public')->assertMissing($primera);
    $segunda = $r->fresh()->foto_path;

    // Una copia comparte el archivo: borrar el original no la deja sin foto.
    $this->actingAs($u)->post("/recetas/{$r->id}/duplicar");
    $copia = Receta::where('receta_origen_id', $r->id)->first();
    $this->actingAs($u)->delete("/recetas/{$r->id}");
    Storage::disk('public')->assertExists($segunda);

    $this->actingAs($u)->delete("/recetas/{$copia->id}");
    Storage::disk('public')->assertMissing($segunda);

    // Al borrar la cuenta se van sus fotos.
    $this->actingAs($u)->post('/recetas', $datos(UploadedFile::fake()->image('c.jpg')));
    $tercera = Receta::where('user_id', $u->id)->latest('id')->first()->foto_path;
    $this->actingAs($u)->delete('/settings/profile', ['password' => 'password']);
    Storage::disk('public')->assertMissing($tercera);
});

it('las cuentas de ejemplo no suben fotos y al limpiarlas no queda nada', function () {
    Storage::fake('public');
    $demo = User::factory()->create(['es_demo' => true, 'caduca_en' => now()->subHour()]);

    $this->actingAs($demo)->post('/recetas', [
        'titulo' => 'Demo', 'raciones' => 2, 'dificultad' => 'facil', 'pasos' => ['x'],
        'ingredientes' => [['texto' => 'x', 'alimento_id' => null, 'cantidad' => 1, 'unidad' => 'ud']], 'foto' => UploadedFile::fake()->image('d.jpg'),
    ])->assertSessionHasErrors('foto');

    $r = Receta::create(['user_id' => $demo->id, 'titulo' => 'Vieja', 'slug' => 'vieja', 'raciones' => 1, 'dificultad' => 'facil', 'etiquetas' => [], 'pasos' => ['x'], 'foto_path' => 'recetas/vieja.jpg']);
    Storage::disk('public')->put('recetas/vieja.jpg', 'x');
    app(LimpiarCuentasDemo::class)->ejecutar();
    Storage::disk('public')->assertMissing('recetas/vieja.jpg');
    expect(Receta::find($r->id))->toBeNull();
});
