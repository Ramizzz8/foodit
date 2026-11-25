<?php

use App\Mercadona\SincronizarCatalogo;
use App\Models\PrecioHistorico;
use App\Models\ProductoMercadona;
use App\Models\Sincronizacion;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function fixtureMercadona(string $nombre): array
{
    return json_decode(file_get_contents(base_path("tests/Fixtures/mercadona/{$nombre}.json")), true);
}

function fakeMercadona(array $cambios = []): void
{
    Http::fake([
        'tienda.mercadona.es/api/categories/112/*' => $cambios[112] ?? Http::response(fixtureMercadona('sub112')),
        'tienda.mercadona.es/api/categories/77/*' => $cambios[77] ?? Http::response(fixtureMercadona('sub77')),
        'tienda.mercadona.es/api/categories/27/*' => $cambios[27] ?? Http::response(fixtureMercadona('sub27')),
        'tienda.mercadona.es/api/categories/*' => $cambios['raiz'] ?? Http::response(fixtureMercadona('raiz')),
    ]);
}

it('guarda productos con envase y precio', function () {
    fakeMercadona();

    $s = app(SincronizarCatalogo::class)->ejecutar();

    expect($s->estado)->toBe('ok')->and($s->productos)->toBe(9);
    expect(ProductoMercadona::find('4241'))
        ->nombre->toBe('Aceite de oliva 0,4º Hacendado')
        ->envase->toBe('Garrafa')
        ->cantidad_envase->toBe(5000.0)
        ->unidad_envase->toBe('ml')
        ->precio->toBe(16.75)
        ->categoria->toBe('Aceite, especias y salsas')
        ->subcategoria->toBe('Aceite de oliva');
    expect(ProductoMercadona::find('30167'))->cantidad_envase->toBe(24.0)->unidad_envase->toBe('ud');
    expect(ProductoMercadona::find('3819'))->cantidad_envase->toBe(160.0)->unidad_envase->toBe('g')->aproximado->toBeTrue();
});

it('apunta el cambio de precio en el histórico', function () {
    ProductoMercadona::factory()->create(['id' => '4241', 'precio' => 17.25]);
    fakeMercadona();

    app(SincronizarCatalogo::class)->ejecutar();

    expect(PrecioHistorico::where('producto_mercadona_id', '4241')->pluck('precio')->all())->toBe([16.75])
        ->and(PrecioHistorico::where('producto_mercadona_id', '4240')->count())->toBe(1); // producto nuevo: primer precio
});

it('no apunta nada si el precio no cambia', function () {
    fakeMercadona();
    app(SincronizarCatalogo::class)->ejecutar();
    app(SincronizarCatalogo::class)->ejecutar();

    expect(PrecioHistorico::where('producto_mercadona_id', '4241')->count())->toBe(1);
});

it('si Mercadona falla no toca el catálogo', function (Closure $respuesta) {
    ProductoMercadona::factory()->create(['id' => '4241', 'precio' => 17.25, 'nombre' => 'Aceite antiguo']);
    fakeMercadona([112 => $respuesta()]);

    $s = app(SincronizarCatalogo::class)->ejecutar();

    expect($s->estado)->toBe('error')->and($s->error)->not->toBeEmpty()
        ->and(ProductoMercadona::find('4241'))->precio->toBe(17.25)->nombre->toBe('Aceite antiguo');
})->with([
    'error 500' => fn () => fn () => Http::response('Server error', 500),
    'html' => fn () => fn () => Http::response('<html>Mantenimiento</html>', 200, ['Content-Type' => 'text/html']),
    'json con otra forma' => fn () => fn () => Http::response(['resultados' => []]),
]);

it('ignora categorías que no son de alimentación', function () {
    fakeMercadona();

    app(SincronizarCatalogo::class)->ejecutar();

    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/categories/226/'));
});

it('pide los datos del almacén configurado con el user agent de Foodit', function () {
    fakeMercadona();

    app(SincronizarCatalogo::class)->ejecutar();

    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'wh=vlc1') && $r->header('User-Agent') === ['Foodit/1.0']);
});

it('se puede lanzar desde la consola', function () {
    fakeMercadona();

    $this->artisan('foodit:mercadona')->assertSuccessful();

    expect(Sincronizacion::latest('id')->first()->estado)->toBe('ok');
});
