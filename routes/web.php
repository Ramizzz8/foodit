<?php

use App\Http\Controllers\AlimentoController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\ObjetivosController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RecetaController;
use App\Models\Receta;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Una receta solo existe para quien puede verla: las de Foodit y las propias. Las de otros dan 404.
Route::bind('receta', fn (string $id) => Receta::visiblesPara(auth()->user())->findOrFail($id));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/plan')->name('dashboard');

    Route::get('plan/{fecha?}', [PlanController::class, 'semana'])->name('plan');
    Route::put('plan/{fecha}/{momento}', [PlanController::class, 'poner'])->name('plan.poner');
    Route::delete('plan/{fecha}/{momento}', [PlanController::class, 'quitar'])->name('plan.quitar');
    Route::post('plan/{fecha}/copiar-anterior', [PlanController::class, 'copiarAnterior'])->name('plan.copiar');

    Route::get('ajustes/objetivos', [ObjetivosController::class, 'edit'])->name('objetivos.edit');
    Route::put('ajustes/objetivos', [ObjetivosController::class, 'update'])->name('objetivos.update');

    Route::get('lista/{fecha?}', [ListaController::class, 'semana'])->name('lista');
    Route::get('lista/{fecha}/imprimir', [ListaController::class, 'imprimir'])->name('lista.imprimir');
    Route::put('lista/{fecha}/marcas/{alimento}', [ListaController::class, 'marcar'])->name('lista.marcar');
    Route::post('lista/{fecha}/extras', [ListaController::class, 'anadirExtra'])->name('lista.extras.anadir');
    Route::put('lista/{fecha}/extras/{extra}', [ListaController::class, 'cambiarExtra'])->whereNumber('extra')->name('lista.extras.cambiar');
    Route::delete('lista/{fecha}/extras/{extra}', [ListaController::class, 'quitarExtra'])->whereNumber('extra')->name('lista.extras.quitar');

    Route::get('alimentos/buscar', [AlimentoController::class, 'buscar'])->name('alimentos.buscar');
    Route::get('alimentos/{alimento}/productos', [AlimentoController::class, 'productos'])->name('alimentos.productos');
    Route::put('alimentos/{alimento}/producto', [AlimentoController::class, 'elegirProducto'])->name('alimentos.producto');

    Route::resource('recetas', RecetaController::class)->parameters(['recetas' => 'receta']);
    Route::post('recetas/{receta}/duplicar', [RecetaController::class, 'duplicar'])->name('recetas.duplicar');
    Route::post('recetas/{receta}/guardar', [RecetaController::class, 'guardar'])->name('recetas.guardar');
    Route::delete('recetas/{receta}/guardar', [RecetaController::class, 'olvidar'])->name('recetas.olvidar');
});

require __DIR__.'/settings.php';
