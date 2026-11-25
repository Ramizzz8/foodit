<?php

use App\Compra\ListaCompra;
use App\Models\Alimento;
use App\Models\ComidaPlan;
use App\Models\IngredienteReceta;
use App\Models\ListaExtra;
use App\Models\PrecioHistorico;
use App\Models\ProductoMercadona;
use App\Models\Receta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 12:00'));
    $this->lunes = CarbonImmutable::parse('2026-11-23');
    $this->u = User::factory()->create();

    $this->arroz = Alimento::factory()->create(['nombre' => 'arroz redondo', 'producto_mercadona_id' => ProductoMercadona::factory()->create([
        'nombre' => 'Arroz redondo Hacendado', 'cantidad_envase' => 1000, 'unidad_envase' => 'g', 'precio' => 1.20,
        'categoria' => 'Arroz, legumbres y pasta', 'orden_pasillo' => 13])->id]);
    $this->huevo = Alimento::factory()->create(['nombre' => 'huevo', 'gramos_por_unidad' => 60, 'producto_mercadona_id' => ProductoMercadona::factory()->create([
        'nombre' => 'Huevos grandes L', 'cantidad_envase' => 12, 'unidad_envase' => 'ud', 'precio' => 2.70,
        'categoria' => 'Huevos, leche y mantequilla', 'orden_pasillo' => 6])->id]);
    $this->sal = Alimento::factory()->create(['nombre' => 'sal']);
});

/** Receta de 2 raciones con los ingredientes dados: [alimento, cantidad, unidad]. */
function recetaCon(array $ingredientes): Receta
{
    $r = Receta::factory()->create(['raciones' => 2]);
    foreach ($ingredientes as $n => [$a, $cantidad, $unidad]) {
        IngredienteReceta::factory()->for($r)->create(['alimento_id' => $a?->id, 'cantidad' => $cantidad, 'unidad' => $unidad, 'texto' => $a?->nombre ?? 'algo raro', 'orden' => $n]);
    }

    return $r;
}

function planificar(User $u, Receta $r, string $fecha, string $momento = 'comida', float $raciones = 2): void
{
    ComidaPlan::factory()->for($u)->create(['fecha' => $fecha, 'momento' => $momento, 'receta_id' => $r->id, 'raciones' => $raciones]);
}

function lineas(array $lista): Collection
{
    return collect($lista['grupos'])->flatMap(fn ($g) => $g['lineas'])->keyBy('alimento');
}

it('junta el mismo alimento de varias recetas', function () {
    planificar($this->u, recetaCon([[$this->arroz, 300, 'g']]), '2026-11-23');
    planificar($this->u, recetaCon([[$this->arroz, 500, 'g']]), '2026-11-24', 'cena', 1); // media receta: 250 g

    expect(lineas(app(ListaCompra::class)->para($this->u, $this->lunes))['arroz redondo'])
        ->gramos->toBe(550.0)->envases->toBe(1);
});

it('redondea a envases y separa lo que sobra', function () {
    planificar($this->u, recetaCon([[$this->arroz, 1200, 'g']]), '2026-11-23');

    $lista = app(ListaCompra::class)->para($this->u, $this->lunes);

    expect(lineas($lista)['arroz redondo'])->envases->toBe(2)->comprado->toBe(2000.0)->sobra->toBe(800.0)->coste->toBe(2.40)
        ->and($lista['total_tienda'])->toBe(2.40)
        ->and($lista['total_comido'])->toBe(1.44);
});

it('vende los huevos por docenas', function () {
    planificar($this->u, recetaCon([[$this->huevo, 5, 'ud']]), '2026-11-23');

    expect(lineas(app(ListaCompra::class)->para($this->u, $this->lunes))['huevo'])
        ->envases->toBe(1)->comprado->toBe(12.0)->sobra->toBe(7.0)->unidad->toBe('ud');
});

it('lo que no tiene producto va aparte y no suma', function () {
    $sinProducto = Alimento::factory()->create(['nombre' => 'lima kaffir']);
    planificar($this->u, recetaCon([[$this->arroz, 500, 'g'], [$sinProducto, 2, 'g'], [null, 1, 'ud']]), '2026-11-23');

    $lista = app(ListaCompra::class)->para($this->u, $this->lunes);

    expect(collect($lista['sin_producto'])->pluck('alimento')->all())->toBe(['algo raro', 'lima kaffir'])
        ->and($lista['total_tienda'])->toBe(1.20);
});

it('lo que va al gusto se recuerda aparte sin comprarlo', function () {
    planificar($this->u, recetaCon([[$this->arroz, 500, 'g'], [$this->sal, null, 'al_gusto']]), '2026-11-23');

    $lista = app(ListaCompra::class)->para($this->u, $this->lunes);

    expect($lista['al_gusto'])->toBe(['sal'])->and(lineas($lista)->keys()->all())->toBe(['arroz redondo']);
});

it('ordena por pasillos', function () {
    planificar($this->u, recetaCon([[$this->arroz, 500, 'g'], [$this->huevo, 2, 'ud']]), '2026-11-23');

    expect(collect(app(ListaCompra::class)->para($this->u, $this->lunes)['grupos'])->pluck('categoria')->all())
        ->toBe(['Huevos, leche y mantequilla', 'Arroz, legumbres y pasta']);
});

it('solo cuenta la semana pedida y las comidas con receta', function () {
    planificar($this->u, recetaCon([[$this->arroz, 500, 'g']]), '2026-11-30');
    ComidaPlan::factory()->for($this->u)->create(['fecha' => '2026-11-24', 'momento' => 'cena', 'tipo' => 'fuera', 'receta_id' => null]);

    expect(app(ListaCompra::class)->para($this->u, $this->lunes)['grupos'])->toBe([]);
});

it('avisa si el precio cambió esta semana', function () {
    $p = $this->arroz->producto;
    PrecioHistorico::create(['producto_mercadona_id' => $p->id, 'precio' => 1.00, 'fecha' => '2026-11-01']);
    PrecioHistorico::create(['producto_mercadona_id' => $p->id, 'precio' => 1.20, 'fecha' => '2026-11-22']);
    planificar($this->u, recetaCon([[$this->arroz, 500, 'g']]), '2026-11-23');

    expect(lineas(app(ListaCompra::class)->para($this->u, $this->lunes))['arroz redondo']['producto']['cambio'])->toBe(0.20);
});

it('tachar no cambia el total pero se recuerda', function () {
    planificar($this->u, recetaCon([[$this->arroz, 500, 'g']]), '2026-11-23');

    $this->actingAs($this->u)->put("/lista/2026-11-23/marcas/{$this->arroz->id}", ['tachado' => true])->assertRedirect();
    $lista = app(ListaCompra::class)->para($this->u, $this->lunes);

    expect(lineas($lista)['arroz redondo']['tachado'])->toBeTrue()->and($lista['total_tienda'])->toBe(1.20);
});

it('añade, tacha y quita cosas sueltas', function () {
    $this->actingAs($this->u)->post('/lista/2026-11-23/extras', ['texto' => 'Papel de cocina'])->assertRedirect();
    $extra = ListaExtra::sole();
    $this->actingAs($this->u)->put("/lista/2026-11-23/extras/{$extra->id}", ['tachado' => true])->assertRedirect();

    expect(app(ListaCompra::class)->para($this->u, $this->lunes)['extras'])->toBe([['id' => $extra->id, 'texto' => 'Papel de cocina', 'tachado' => true]]);

    $this->actingAs($this->u)->delete("/lista/2026-11-23/extras/{$extra->id}")->assertRedirect();
    expect(ListaExtra::count())->toBe(0);
});

it('la lista de otro no existe', function () {
    $extraAjeno = ListaExtra::create(['user_id' => User::factory()->create()->id, 'lunes' => '2026-11-23', 'texto' => 'Ajeno']);

    $this->actingAs($this->u)->put("/lista/2026-11-23/extras/{$extraAjeno->id}", ['tachado' => true])->assertNotFound();
    $this->actingAs($this->u)->delete("/lista/2026-11-23/extras/{$extraAjeno->id}")->assertNotFound();
    expect($extraAjeno->fresh()->tachado)->toBeFalse();
});

it('muestra la lista y su versión para imprimir', function () {
    planificar($this->u, recetaCon([[$this->arroz, 500, 'g']]), '2026-11-23');

    $this->actingAs($this->u)->get('/lista')->assertInertia(fn ($p) => $p->component('lista/semana')->where('lunes', '2026-11-23')->has('lista.grupos', 1));
    $this->actingAs($this->u)->get('/lista/2026-11-23/imprimir')->assertInertia(fn ($p) => $p->component('lista/imprimir'));
    $this->actingAs($this->u)->get('/lista/2026-11-26')->assertRedirect('/lista/2026-11-23');
});
