<?php

use App\Cocina\Alergenos;
use App\Models\Alimento;
use App\Models\Receta;
use App\Models\User;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;

beforeEach(function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
});

function receta(string $slug): Receta
{
    return Receta::where('slug', $slug)->with('ingredientes.alimento')->firstOrFail();
}

it('todos los alimentos tienen grupo y sus alérgenos son de la lista de la UE', function () {
    Alimento::all()->each(function (Alimento $a) {
        expect($a->grupo)->toBeIn(Alergenos::GRUPOS, $a->nombre);
        expect(array_diff($a->alergenos, Alergenos::TODOS))->toBe([], $a->nombre);
    });
    expect(Alergenos::TODOS)->toHaveCount(14);
});

it('saca los alérgenos de una receta de sus ingredientes', function () {
    expect(receta('espaguetis-carbonara')->alergenos())->toContain('gluten', 'huevo', 'lacteos');
    expect(receta('gazpacho-andaluz')->alergenos())->toContain('gluten');
});

it('sabe si una receta es vegetariana por sus grupos', function () {
    expect(receta('lentejas-estofadas')->grupos())->toContain('cerdo');
    expect(receta('gazpacho-andaluz')->grupos())->not->toContain('carne', 'cerdo', 'pescado', 'marisco');
});

it('comprueba si una receta es compatible con una persona', function () {
    $u = User::factory()->create();
    $carbonara = receta('espaguetis-carbonara');
    $lentejas = receta('lentejas-estofadas');
    $gazpacho = receta('gazpacho-andaluz');

    expect($carbonara->compatibleCon($u))->toBeTrue();

    $u->alergenos = ['lacteos'];
    expect($carbonara->compatibleCon($u))->toBeFalse()
        ->and($gazpacho->compatibleCon($u))->toBeTrue();

    $u->alergenos = [];
    $u->dieta = 'vegetariana';
    expect($lentejas->compatibleCon($u))->toBeFalse()
        ->and($gazpacho->compatibleCon($u))->toBeTrue();

    $u->dieta = 'sin_cerdo';
    expect($lentejas->compatibleCon($u))->toBeFalse();

    $u->dieta = 'omnivora';
    $u->alimentos_vetados = [Alimento::where('nombre', 'pepino')->value('id')];
    expect($gazpacho->compatibleCon($u))->toBeFalse();

    $u->alimentos_vetados = [];
    $u->minutos_entre_semana = 20;
    expect($lentejas->minutos)->toBeGreaterThan(20)
        ->and($lentejas->compatibleCon($u))->toBeFalse()
        ->and($lentejas->compatibleCon($u, entreSemana: false))->toBeTrue();
});

it('las etiquetas «vegetariana» y «sin gluten» de las recetas cuadran con sus ingredientes', function () {
    Receta::with('ingredientes.alimento')->get()->each(function (Receta $r) {
        if (in_array('vegetariana', $r->etiquetas ?? [])) {
            expect(array_intersect($r->grupos(), ['carne', 'cerdo', 'pescado', 'marisco']))->toBe([], $r->titulo);
        }
        if (in_array('sin gluten', $r->etiquetas ?? [])) {
            expect($r->alergenos())->not->toContain('gluten', $r->titulo);
        }
    });
});
