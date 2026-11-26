<?php

use App\Models\User;
use App\Recetas\ImportarDesdeUrl;
use App\Recetas\LeerIngrediente;
use Database\Seeders\AlimentosSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->seed(AlimentosSeeder::class));

/** Importador con un DNS falso: cada host resuelve a la IP que diga el test. */
function importador(array $dns = []): ImportarDesdeUrl
{
    return new ImportarDesdeUrl(fn (string $host) => $dns[$host] ?? ['93.184.216.34']);
}

function paginaCon(array $jsonLd): string
{
    return '<html><head><title>x</title><script type="application/ld+json">'.json_encode($jsonLd, JSON_UNESCAPED_UNICODE).'</script></head><body>Hola</body></html>';
}

const LENTEJAS = [
    '@context' => 'https://schema.org', '@type' => 'Recipe', 'name' => 'Lentejas de la abuela', 'description' => 'Un clásico.',
    'recipeYield' => '4 raciones', 'totalTime' => 'PT1H10M',
    'recipeIngredient' => ['300 g de lentejas pardinas', '1 cebolla', '2 cucharadas de aceite de oliva', '1/2 kg de patatas', 'Sal al gusto'],
    'recipeInstructions' => [['@type' => 'HowToStep', 'text' => 'Pocha la cebolla.'], ['@type' => 'HowToStep', 'text' => 'Añade las lentejas y cuece 40 minutos.']],
];

it('lee una receta schema.org', function () {
    Http::fake(['https://cocina.example/lentejas' => Http::response(paginaCon(LENTEJAS))]);

    $b = importador()->ejecutar('https://cocina.example/lentejas');

    expect($b['titulo'])->toBe('Lentejas de la abuela')
        ->and($b['raciones'])->toBe(4)
        ->and($b['minutos'])->toBe(70)
        ->and($b['pasos'])->toBe(['Pocha la cebolla.', 'Añade las lentejas y cuece 40 minutos.'])
        ->and($b['ingredientes'])->toHaveCount(5);
    expect($b['ingredientes'][0])->toMatchArray(['texto' => '300 g de lentejas pardinas', 'cantidad' => 300.0, 'unidad' => 'g', 'alimento' => 'lenteja pardina']);
    expect($b['ingredientes'][1])->toMatchArray(['cantidad' => 1.0, 'unidad' => 'ud', 'alimento' => 'cebolla']);
    expect($b['ingredientes'][2])->toMatchArray(['cantidad' => 2.0, 'unidad' => 'cda', 'alimento' => 'aceite de oliva virgen extra']);
    expect($b['ingredientes'][3])->toMatchArray(['cantidad' => 0.5, 'unidad' => 'kg', 'alimento' => 'patata']);
    expect($b['ingredientes'][4])->toMatchArray(['cantidad' => null, 'unidad' => 'al_gusto', 'alimento' => 'sal']);
});

it('encuentra la receta dentro de @graph y con pasos en texto', function () {
    Http::fake(['*' => Http::response(paginaCon(['@context' => 'https://schema.org', '@graph' => [
        ['@type' => 'WebPage', 'name' => 'Portada'],
        ['@type' => ['Recipe', 'NewsArticle'], 'name' => 'Gazpacho', 'recipeYield' => ['6', '6 vasos'], 'recipeIngredient' => ['1 kg de tomates'], 'recipeInstructions' => "Tritura todo.\nEnfría."],
    ]]))]);

    $b = importador()->ejecutar('https://cocina.example/gazpacho');

    expect($b['titulo'])->toBe('Gazpacho')->and($b['raciones'])->toBe(6)->and($b['pasos'])->toBe(['Tritura todo.', 'Enfría.']);
});

it('rechaza direcciones internas, también tras una redirección', function (string $url, array $dns) {
    Http::fake(['https://publica.example/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data'])]);

    expect(fn () => importador($dns)->ejecutar($url))->toThrow(InvalidArgumentException::class);
})->with([
    'bucle local' => ['http://127.0.0.1/receta', []],
    'metadatos de la nube' => ['http://169.254.169.254/latest', []],
    'dominio que resuelve a una IP privada' => ['https://interno.example/receta', ['interno.example' => ['10.0.0.5']]],
    'IPv6 local' => ['http://[::1]/receta', []],
    'otro esquema' => ['file:///etc/passwd', []],
    'redirección a metadatos' => ['https://publica.example/receta', []],
]);

it('dice claramente si la página no trae receta', function () {
    Http::fake(['*' => Http::response('<html><body>Solo texto</body></html>')]);

    expect(fn () => importador()->ejecutar('https://cocina.example/nada'))
        ->toThrow(InvalidArgumentException::class, 'no trae una receta');
});

it('no descarga páginas enormes', function () {
    Http::fake(['*' => Http::response(str_repeat('a', 1_100_000))]);

    expect(fn () => importador()->ejecutar('https://cocina.example/enorme'))->toThrow(InvalidArgumentException::class);
});

it('desde la app deja la receta importada como borrador para revisar', function () {
    Http::fake(['https://cocina.example/lentejas' => Http::response(paginaCon(LENTEJAS))]);
    app()->instance(ImportarDesdeUrl::class, importador());
    $u = User::factory()->create();

    $this->actingAs($u)->post('/recetas/importar-url', ['url' => 'https://cocina.example/lentejas'])->assertRedirect('/recetas/crear');
    $this->actingAs($u)->get('/recetas/crear')->assertInertia(fn ($p) => $p
        ->component('recetas/form')->where('borrador.titulo', 'Lentejas de la abuela')->has('borrador.ingredientes', 5));

    $this->actingAs($u)->post('/recetas/importar-url', ['url' => 'http://127.0.0.1/x'])->assertSessionHasErrors('url');
});

it('entiende las cantidades y unidades más comunes', function (string $texto, ?float $cantidad, string $unidad) {
    expect(LeerIngrediente::leer($texto))->toMatchArray(['cantidad' => $cantidad, 'unidad' => $unidad]);
})->with([
    ['200 g de arroz', 200.0, 'g'],
    ['200gr arroz', 200.0, 'g'],
    ['1,5 l de caldo', 1.5, 'l'],
    ['250 ml de leche', 250.0, 'ml'],
    ['1 cucharadita de comino', 1.0, 'cdta'],
    ['3 cdas. de aceite', 3.0, 'cda'],
    ['una pizca de sal', 1.0, 'pizca'],
    ['2 dientes de ajo', 2.0, 'ud'],
    ['1/2 cebolla', 0.5, 'ud'],
    ['pimienta', null, 'al_gusto'],
]);
