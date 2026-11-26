<?php

use App\Models\Receta;
use App\Models\User;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;

it('cada foto existe, pesa poco y tiene una licencia libre con su autor', function () {
    $fotos = require database_path('data/fotos.php');

    foreach (['platos', 'ingredientes'] as $grupo) {
        foreach ($fotos[$grupo] as $clave => $f) {
            $archivo = public_path("images/{$grupo}/{$clave}.webp");
            expect(file_exists($archivo))->toBeTrue($archivo)
                ->and(filesize($archivo))->toBeLessThanOrEqual(200_000)
                ->and($f['licencia'])->toMatch('/^(CC0|CC BY \d\.\d|dominio público)$/')
                ->and($f['autor'])->not->toBeEmpty()
                ->and($f['enlace'])->toStartWith('https://');
        }
    }
});

it('las recetas con foto la enseñan con su crédito', function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);
    $gazpacho = Receta::firstWhere('slug', 'gazpacho-andaluz');

    expect($gazpacho->foto_url)->toBe('/images/platos/gazpacho-andaluz.webp')
        ->and($gazpacho->foto_credito)->toContain('CC BY');

    $this->actingAs(User::factory()->create())->get("/recetas/{$gazpacho->id}")
        ->assertInertia(fn ($p) => $p->where('receta.foto', '/images/platos/gazpacho-andaluz.webp')->where('receta.foto_credito', $gazpacho->foto_credito));
});

it('la portada da los créditos de todas las fotos', function () {
    $fotos = require database_path('data/fotos.php');

    $this->get('/')->assertInertia(fn ($p) => $p->has('creditos', count($fotos['platos']) + count($fotos['ingredientes']))
        ->has('creditos.0', fn ($c) => $c->hasAll(['titulo', 'autor', 'licencia', 'enlace'])->etc()));
});

it('todas las cenas del tique de la portada tienen foto real del plato', function () {
    $this->seed(AlimentosSeeder::class);
    $this->seed(RecetasSeeder::class);

    $this->get('/')->assertInertia(function ($p) {
        $tique = $p->toArray()['props']['tique'];
        expect($tique)->toHaveCount(7);
        foreach ($tique as $dia) {
            expect($dia['opciones'])->toHaveCount(3);
            foreach ($dia['opciones'] as $o) {
                expect($o['plato'])->not->toBeNull($o['titulo'])->and(file_exists(public_path(ltrim($o['plato'], '/'))))->toBeTrue($o['titulo']);
            }
        }
    });
});
