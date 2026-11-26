<?php

use App\Http\Controllers\AlimentoController;
use App\Http\Controllers\BienvenidaController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\DespensaController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\ObjetivosController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PortadaController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\SaludController;
use App\Http\Middleware\PerfilCompleto;
use App\Models\Receta;
use Illuminate\Support\Facades\Route;

Route::get('/', PortadaController::class)->name('home');

Route::post('demo/entrar', [DemoController::class, 'entrar'])->name('demo.entrar');
Route::inertia('privacidad', 'privacidad')->name('privacidad');

// Verificar el email no bloquea: se usa la app desde el primer momento, con un aviso hasta verificarlo.
// Una receta solo existe para quien puede verla: las de Foodit y las propias. Las de otros dan 404.
Route::bind('receta', fn (string $id) => Receta::visiblesPara(auth()->user())->findOrFail($id));

// El alta guiada (y el buscador de alimentos que usa) va antes de exigir el perfil completo.
Route::middleware(['auth'])->group(function () {
    Route::redirect('bienvenida', '/bienvenida/1');
    Route::get('bienvenida/{paso}', [BienvenidaController::class, 'mostrar'])->whereIn('paso', ['1', '2', '3', '4'])->name('bienvenida');
    Route::put('bienvenida/{paso}', [BienvenidaController::class, 'guardar'])->whereIn('paso', ['1', '2', '3', '4'])->name('bienvenida.guardar');
    Route::get('alimentos/buscar', [AlimentoController::class, 'buscar'])->name('alimentos.buscar');
});

Route::middleware(['auth', PerfilCompleto::class])->group(function () {
    Route::redirect('dashboard', '/plan')->name('dashboard');

    Route::get('plan/{fecha?}', [PlanController::class, 'semana'])->name('plan');
    Route::put('plan/{fecha}/{momento}', [PlanController::class, 'poner'])->name('plan.poner');
    Route::delete('plan/{fecha}/{momento}', [PlanController::class, 'quitar'])->name('plan.quitar');
    Route::post('plan/{fecha}/copiar-anterior', [PlanController::class, 'copiarAnterior'])->name('plan.copiar');
    Route::post('plan/{fecha}/generar', [PlanController::class, 'generar'])->name('plan.generar');

    Route::get('ajustes/objetivos', [ObjetivosController::class, 'edit'])->name('objetivos.edit');
    Route::get('ajustes/salud', [SaludController::class, 'show'])->name('salud');
    Route::get('despensa', [DespensaController::class, 'index'])->name('despensa');
    Route::post('despensa', [DespensaController::class, 'store'])->name('despensa.anadir');
    Route::delete('despensa/{alimento}', [DespensaController::class, 'destroy'])->name('despensa.quitar');
    Route::delete('ajustes/salud', [SaludController::class, 'destroy'])->name('salud.borrar');
    Route::put('ajustes/objetivos', [ObjetivosController::class, 'update'])->name('objetivos.update');

    Route::get('lista/{fecha?}', [ListaController::class, 'semana'])->name('lista');
    Route::get('lista/{fecha}/imprimir', [ListaController::class, 'imprimir'])->name('lista.imprimir');
    Route::put('lista/{fecha}/marcas/{alimento}', [ListaController::class, 'marcar'])->name('lista.marcar');
    Route::post('lista/{fecha}/extras', [ListaController::class, 'anadirExtra'])->name('lista.extras.anadir');
    Route::put('lista/{fecha}/extras/{extra}', [ListaController::class, 'cambiarExtra'])->whereNumber('extra')->name('lista.extras.cambiar');
    Route::delete('lista/{fecha}/extras/{extra}', [ListaController::class, 'quitarExtra'])->whereNumber('extra')->name('lista.extras.quitar');

    Route::get('alimentos/{alimento}/productos', [AlimentoController::class, 'productos'])->name('alimentos.productos');
    Route::put('alimentos/{alimento}/producto', [AlimentoController::class, 'elegirProducto'])->name('alimentos.producto');

    Route::resource('recetas', RecetaController::class)->parameters(['recetas' => 'receta']);
    Route::post('recetas/{receta}/duplicar', [RecetaController::class, 'duplicar'])->name('recetas.duplicar');
    Route::post('recetas/{receta}/guardar', [RecetaController::class, 'guardar'])->name('recetas.guardar');
    Route::delete('recetas/{receta}/guardar', [RecetaController::class, 'olvidar'])->name('recetas.olvidar');
});

require __DIR__.'/settings.php';
