<?php

namespace App\Http\Controllers;

use App\Cocina\Alergenos;
use App\Models\Alimento;
use App\Models\User;
use App\Perfil\Calorias;
use App\Plan\GenerarSemana;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** El alta guiada: cuatro preguntas para proponer la primera semana. */
class BienvenidaController extends Controller
{
    private const MENSAJES = [
        'consentimiento_salud.accepted' => 'Marca la casilla para que podamos guardar estos datos.',
        'sexo.required' => 'Elige una opción.',
        'actividad.required' => 'Elige cuánto te mueves.',
        'objetivo_kcal.min' => 'Por debajo de 1.200 kcal al día no te lo vamos a proponer: consúltalo con un profesional.',
    ];

    public function __construct(private GenerarSemana $generar) {}

    /** La primera semana es la actual; el fin de semana, la siguiente. */
    public static function primeraSemana(): CarbonImmutable
    {
        $hoy = CarbonImmutable::today();

        return $hoy->isWeekend() ? $hoy->next('Monday') : $hoy->startOfWeek();
    }

    public function mostrar(Request $request, int $paso): Response
    {
        $u = $request->user();
        $semilla = $request->integer('semilla', random_int(1, 999_999));
        $lunes = self::primeraSemana();

        return Inertia::render('bienvenida', [
            'paso' => $paso,
            'editar' => $request->boolean('editar') && $paso < 4,
            'consentido' => $u->consentimiento_salud_en !== null,
            'perfil' => [
                ...$u->only('sexo', 'edad', 'altura_cm', 'actividad', 'meta', 'objetivo_kcal', 'dieta', 'planifica_desayuno', 'minutos_entre_semana'),
                'peso_kg' => $u->peso_kg === null ? null : (float) $u->peso_kg,
                'presupuesto_semanal' => (float) $u->presupuesto_semanal,
                'alergenos' => $u->alergenos ?? [],
                'vetados' => Alimento::whereIn('id', $u->alimentos_vetados ?? [])->get(['id', 'nombre']),
            ],
            'propuesta' => $paso === 4 ? ['semilla' => $semilla, 'lunes' => $lunes->toDateString(), ...$this->generar->para($u, $lunes, $semilla)] : null,
            'alergenos' => collect(Alergenos::NOMBRES)->map(fn ($nombre, $clave) => ['clave' => $clave, 'nombre' => $nombre])->values(),
        ]);
    }

    public function guardar(Request $request, int $paso): RedirectResponse
    {
        $u = $request->user();

        match ($paso) {
            1 => $this->tu($request, $u),
            2 => $this->loQueNoComes($request, $u),
            3 => $this->tuSemana($request, $u),
            4 => $this->empezar($request, $u),
        };

        if ($request->boolean('editar') && $paso < 4) {
            return redirect('/ajustes/salud')->with('exito', 'Cambios guardados.');
        }

        return $paso < 4 ? redirect('/bienvenida/'.($paso + 1)) : redirect('/plan');
    }

    /** Los datos de salud se guardan solo con el consentimiento marcado (la primera vez). */
    private function consentimiento(User $u): array
    {
        return $u->consentimiento_salud_en === null ? ['consentimiento_salud' => ['accepted']] : [];
    }

    private function tu(Request $request, User $u): void
    {
        $d = $request->validate([
            ...$this->consentimiento($u),
            'sexo' => ['required', Rule::in(['mujer', 'hombre'])],
            'edad' => ['required', 'integer', 'min:14', 'max:100'],
            'altura_cm' => ['required', 'integer', 'min:120', 'max:230'],
            'peso_kg' => ['required', 'numeric', 'min:30', 'max:300'],
            'actividad' => ['required', Rule::in(array_keys(Calorias::ACTIVIDAD))],
            'meta' => ['required', Rule::in(array_keys(Calorias::META))],
            'objetivo_kcal' => ['nullable', 'integer', 'min:'.Calorias::MINIMO, 'max:5000'],
        ], self::MENSAJES);
        $d['objetivo_kcal'] ??= Calorias::objetivo($d['sexo'], (int) $d['edad'], (float) $d['altura_cm'], (float) $d['peso_kg'], $d['actividad'], $d['meta']);
        unset($d['consentimiento_salud']);

        $u->forceFill([...$d, 'consentimiento_salud_en' => $u->consentimiento_salud_en ?? now()])->save();
    }

    private function loQueNoComes(Request $request, User $u): void
    {
        $d = $request->validate([
            ...$this->consentimiento($u),
            'alergenos' => ['array'],
            'alergenos.*' => [Rule::in(Alergenos::TODOS)],
            'dieta' => ['required', Rule::in(Alergenos::DIETAS)],
            'alimentos_vetados' => ['array', 'max:30'],
            'alimentos_vetados.*' => ['integer', 'exists:alimentos,id'],
        ], self::MENSAJES);

        $u->forceFill([
            'alergenos' => array_values($d['alergenos'] ?? []),
            'dieta' => $d['dieta'],
            'alimentos_vetados' => array_values(array_map('intval', $d['alimentos_vetados'] ?? [])),
            'consentimiento_salud_en' => $u->consentimiento_salud_en ?? now(),
        ])->save();
    }

    private function empezar(Request $request, User $u): void
    {
        $semilla = (int) ($request->validate(['semilla' => ['nullable', 'integer', 'min:0']])['semilla'] ?? 0);
        $lunes = self::primeraSemana();
        $this->generar->aplicar($u, $lunes, $this->generar->para($u, $lunes, $semilla));
        $u->forceFill(['perfil_completo_en' => $u->perfil_completo_en ?? now()])->save();
    }

    private function tuSemana(Request $request, User $u): void
    {
        $d = $request->validate([
            'presupuesto_semanal' => ['required', 'numeric', 'min:10', 'max:500'],
            'planifica_desayuno' => ['boolean'],
            'minutos_entre_semana' => ['nullable', Rule::in([20, 40])],
        ]);

        $u->forceFill([
            'presupuesto_semanal' => $d['presupuesto_semanal'],
            'planifica_desayuno' => (bool) ($d['planifica_desayuno'] ?? false),
            'minutos_entre_semana' => $d['minutos_entre_semana'] ?? null,
        ])->save();
    }
}
