<?php

use App\Models\Alimento;
use App\Models\ComidaPlan;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use App\Models\User;
use App\Plan\GenerarSemana;
use Carbon\CarbonImmutable;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;

beforeEach(function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    // Un catálogo de prueba: cada producto con un precio distinto (1–6 € el kilo o el litro).
    Alimento::whereNotNull('producto_mercadona_id')->get()->each(fn (Alimento $a) => ProductoMercadona::factory()->create([
        'id' => $a->producto_mercadona_id, 'cantidad_envase' => 1000, 'unidad_envase' => 'g', 'precio' => 1 + (crc32($a->nombre) % 500) / 100,
    ]));
    $this->lunes = CarbonImmutable::parse('2026-11-30');
});

function generar(User $u, int $semilla = 1): array
{
    return app(GenerarSemana::class)->para($u, test()->lunes, $semilla);
}

/** @return array<int, Receta> */
function recetasDe(array $semana): array
{
    return Receta::with('ingredientes.alimento')->findMany(collect($semana['comidas'])->pluck('receta_id'))->keyBy('id')->all();
}

it('propone una semana completa que cuadra con las calorías', function () {
    $u = User::factory()->create(['objetivo_kcal' => 2000, 'planifica_desayuno' => true]);
    $s = generar($u);

    expect($s['comidas'])->toHaveCount(21)->and($s['aviso'])->toBeNull();
    $recetas = recetasDe($s);
    foreach (collect($s['comidas'])->groupBy('fecha') as $fecha => $comidas) {
        expect($comidas->sum('kcal'))->toBeGreaterThanOrEqual(2000 * 0.85, $fecha)->toBeLessThanOrEqual(2000 * 1.15, $fecha);
        foreach ($comidas as $c) {
            expect($c['raciones'])->toBeIn([1.0, 1.5, 2.0]);
            expect(in_array('desayuno', $recetas[$c['receta_id']]->etiquetas ?? []))->toBe($c['momento'] === 'desayuno');
        }
    }
});

it('no repite una comida o cena más de dos veces ni dos días seguidos', function () {
    foreach ([1, 2, 3] as $semilla) {
        $s = generar(User::factory()->create(), $semilla);
        $principales = collect($s['comidas'])->where('momento', '!=', 'desayuno');

        expect($principales)->toHaveCount(14);
        $principales->countBy('receta_id')->each(fn ($veces) => expect($veces)->toBeLessThanOrEqual(2));
        $porDia = $principales->groupBy('fecha')->map(fn ($c) => $c->pluck('receta_id')->all())->values();
        for ($d = 1; $d < 7; $d++) {
            expect(array_intersect($porDia[$d], $porDia[$d - 1]))->toBe([], "día {$d}, semilla {$semilla}");
            expect(array_unique($porDia[$d]))->toHaveCount(2);
        }
    }
});

it('respeta alergias, dieta, alimentos vetados y el tiempo entre semana', function () {
    $u = User::factory()->create([
        'alergenos' => ['lacteos'], 'dieta' => 'sin_cerdo', 'minutos_entre_semana' => 40,
        'alimentos_vetados' => [Alimento::where('nombre', 'champiñón')->value('id')],
    ]);
    $s = generar($u);
    $recetas = recetasDe($s);

    expect($s['comidas'])->not->toBeEmpty();
    foreach ($s['comidas'] as $c) {
        $entreSemana = CarbonImmutable::parse($c['fecha'])->isWeekday();
        expect($recetas[$c['receta_id']]->compatibleCon($u, $entreSemana))->toBeTrue($recetas[$c['receta_id']]->titulo);
    }
});

it('nunca mete un alérgeno para cuadrar el presupuesto', function () {
    $u = User::factory()->create(['alergenos' => ['lacteos', 'gluten'], 'presupuesto_semanal' => 10]);
    $s = generar($u);
    $recetas = recetasDe($s);

    foreach ($s['comidas'] as $c) {
        expect($recetas[$c['receta_id']]->alergenos())->not->toContain('lacteos')->not->toContain('gluten');
    }
});

it('se ajusta al presupuesto o avisa de que no llega', function () {
    $holgado = generar(User::factory()->create(['presupuesto_semanal' => 500]));
    $justo = generar(User::factory()->create(['presupuesto_semanal' => 35]));
    $imposible = generar(User::factory()->create(['presupuesto_semanal' => 10]));

    expect($justo['coste'])->toBeLessThanOrEqual($holgado['coste']);
    if ($justo['coste'] > 35) {
        expect($justo['aviso'])->toContain('presupuesto');
    }
    expect($imposible['coste'])->toBeGreaterThan(10)->and($imposible['aviso'])->toContain('presupuesto');
    expect(round(collect($justo['comidas'])->sum('coste'), 2))->toBe($justo['coste']);
});

it('la misma semilla da la misma semana y otra semilla, otra', function () {
    $u = User::factory()->create();

    expect(generar($u, 7))->toBe(generar($u, 7));
    $distintas = collect([8, 9, 10])->map(fn ($s) => collect(generar($u, $s)['comidas'])->pluck('receta_id')->all())
        ->push(collect(generar($u, 7)['comidas'])->pluck('receta_id')->all())->unique(fn ($ids) => implode(',', $ids));
    expect($distintas->count())->toBeGreaterThan(1);
});

it('con muy pocas recetas posibles lo dice claro y no falla', function () {
    $u = User::factory()->create(['alergenos' => ['gluten', 'lacteos', 'huevo', 'pescado', 'apio', 'sulfitos'], 'dieta' => 'vegetariana', 'minutos_entre_semana' => 20]);
    $s = generar($u);

    expect($s['aviso'])->toContain('recetas')->and(count($s['comidas']))->toBeLessThan(14);

    $nada = User::factory()->create(['alimentos_vetados' => Alimento::pluck('id')->all()]);
    expect(generar($nada))->toMatchArray(['comidas' => [], 'coste' => 0.0])->and(generar($nada)['aviso'])->not->toBeNull();
});

it('desde el plan sustituye la semana y deja las demás', function () {
    $u = User::factory()->create();
    $otra = Receta::first();
    $u->comidasPlan()->create(['fecha' => '2026-12-01', 'momento' => 'comida', 'receta_id' => $otra->id]);
    $u->comidasPlan()->create(['fecha' => '2026-12-08', 'momento' => 'comida', 'receta_id' => $otra->id]);

    $this->actingAs($u)->post('/plan/2026-11-30/generar', ['semilla' => 3])->assertRedirect();

    $semana = ComidaPlan::where('user_id', $u->id)->whereBetween('fecha', ['2026-11-30', '2026-12-06'])->get();
    $guardadas = $semana->mapWithKeys(fn ($c) => [$c->fecha->toDateString().'|'.$c->momento => [$c->receta_id, $c->raciones]])->sortKeys()->all();
    $propuestas = collect(generar($u, 3)['comidas'])->mapWithKeys(fn ($c) => [$c['fecha'].'|'.$c['momento'] => [$c['receta_id'], $c['raciones']]])->sortKeys()->all();
    expect($semana)->toHaveCount(14)->and($guardadas)->toBe($propuestas);
    expect(ComidaPlan::where('user_id', $u->id)->where('fecha', '2026-12-08')->exists())->toBeTrue();
});

it('el último paso de la bienvenida enseña la semana y la guarda al empezar', function () {
    $u = User::factory()->sinPerfil()->create();

    $this->actingAs($u)->get('/bienvenida/4?semilla=5')->assertInertia(fn ($p) => $p
        ->has('propuesta.comidas', 14)->where('propuesta.semilla', 5)->has('propuesta.coste'));

    $this->actingAs($u)->put('/bienvenida/4', ['semilla' => 5])->assertRedirect('/plan');
    expect($u->comidasPlan()->count())->toBe(14)->and($u->fresh()->perfil_completo_en)->not->toBeNull();
});
