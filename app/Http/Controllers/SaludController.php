<?php

namespace App\Http\Controllers;

use App\Cocina\Alergenos;
use App\Models\Alimento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Ajustes → Tu perfil: ver, cambiar y borrar los datos de salud (lo que promete la página de privacidad). */
class SaludController extends Controller
{
    public function show(Request $request): Response
    {
        $u = $request->user();

        return Inertia::render('ajustes/salud', [
            'salud' => [
                ...$u->only('sexo', 'edad', 'altura_cm', 'actividad', 'meta', 'objetivo_kcal', 'dieta', 'minutos_entre_semana', 'planifica_desayuno'),
                'peso_kg' => $u->peso_kg === null ? null : (float) $u->peso_kg,
                'presupuesto_semanal' => (float) $u->presupuesto_semanal,
                'alergenos' => $u->alergenos ?? [],
                'alergenos_nombres' => array_values(array_map(fn ($a) => Alergenos::NOMBRES[$a] ?? $a, $u->alergenos ?? [])),
                'vetados' => Alimento::whereIn('id', $u->alimentos_vetados ?? [])->pluck('nombre'),
                'consentimiento_salud_en' => $u->consentimiento_salud_en?->toIso8601String(),
            ],
        ]);
    }

    /** Retira el consentimiento: borra los datos de salud. La cuenta, las recetas y el plan se quedan. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'sexo' => null, 'edad' => null, 'altura_cm' => null, 'peso_kg' => null, 'actividad' => null, 'meta' => null,
            'alergenos' => null, 'alimentos_vetados' => null, 'dieta' => 'omnivora', 'consentimiento_salud_en' => null,
        ])->save();

        return redirect('/ajustes/salud')->with('exito', 'Hemos borrado tus datos de salud y tu consentimiento.');
    }
}
