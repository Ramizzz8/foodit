<?php

use App\Http\Controllers\AlimentoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('alimentos/buscar', [AlimentoController::class, 'buscar'])->name('alimentos.buscar');
    Route::get('alimentos/{alimento}/productos', [AlimentoController::class, 'productos'])->name('alimentos.productos');
    Route::put('alimentos/{alimento}/producto', [AlimentoController::class, 'elegirProducto'])->name('alimentos.producto');
});

require __DIR__.'/settings.php';
