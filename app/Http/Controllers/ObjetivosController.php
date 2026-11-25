<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ObjetivosController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('ajustes/objetivos', [
            'objetivos' => $request->user()->only('objetivo_kcal', 'presupuesto_semanal', 'planifica_desayuno'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'objetivo_kcal' => ['required', 'integer', 'min:1000', 'max:5000'],
            'presupuesto_semanal' => ['required', 'numeric', 'min:10', 'max:500'],
            'planifica_desayuno' => ['boolean'],
        ]);

        $request->user()->update([...$datos, 'planifica_desayuno' => (bool) ($datos['planifica_desayuno'] ?? false)]);

        return back()->with('exito', 'Objetivos guardados.');
    }
}
