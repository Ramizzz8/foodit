<?php

namespace App\Plan;

use App\Cocina\CalculadoraReceta;
use App\Models\Receta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Propone una semana de comidas para una persona, sin IA: respeta lo que no come, se acerca a sus calorías
 * ajustando las raciones, no repite demasiado y intenta entrar en su presupuesto. La misma semilla da la misma semana.
 */
class GenerarSemana
{
    public const RACIONES = [1, 1.5, 2];

    /** Margen de calorías que se busca cada día (±10 %); el resultado garantizado cuando hay recetas es ±15 %. */
    private const MARGEN = 0.10;

    public function __construct(private CalculadoraReceta $calculadora) {}

    /**
     * @return array{comidas: list<array{fecha: string, momento: string, receta_id: int, titulo: string, raciones: float, kcal: float, coste: float}>,
     *               coste: float, aviso: ?string}
     */
    public function para(User $u, CarbonImmutable $lunes, int $semilla): array
    {
        $azar = new Randomizer(new Mt19937($semilla));
        $recetas = Receta::visiblesPara($u)->with('ingredientes.alimento.producto')->orderBy('id')->get()
            ->map(fn (Receta $r) => [
                'receta' => $r,
                ...$this->calculadora->porRacion($r, $u),
                'entre_semana' => $r->compatibleCon($u, true),
                'finde' => $r->compatibleCon($u, false),
            ])
            ->filter(fn ($x) => $x['kcal'] > 0)
            ->values();

        $esDesayuno = fn ($x) => in_array('desayuno', $x['receta']->etiquetas ?? [], true);
        $avisos = [];
        $comidas = [];
        $usos = [];
        $ayer = [];

        for ($d = 0; $d < 7; $d++) {
            $fecha = $lunes->addDays($d);
            $entreSemana = $d < 5;
            $validas = $recetas->filter(fn ($x) => $x[$entreSemana ? 'entre_semana' : 'finde']);
            $objetivo = (float) $u->objetivo_kcal;
            $hoy = [];

            if ($u->planifica_desayuno) {
                $desayunos = $validas->filter($esDesayuno)->values()->all();
                if ($desayunos === []) {
                    $avisos['desayuno'] = 'No hay desayunos que encajen con lo que no comes: los hemos dejado en blanco.';
                } else {
                    // El desayuno se puede repetir (mucha gente desayuna lo mismo); se elige al azar entre los posibles.
                    $x = $desayunos[$azar->getInt(0, count($desayunos) - 1)];
                    $comidas[] = $this->comida($fecha, 'desayuno', $x, 1);
                    $objetivo -= $x['kcal'];
                }
            }

            $posibles = $validas->reject($esDesayuno)
                ->filter(fn ($x) => ($usos[$x['receta']->id] ?? 0) < 2 && ! in_array($x['receta']->id, $ayer, true))
                ->values()->all();
            // Orden aleatorio, pero primero las que aún no han salido esta semana: más variedad.
            $posibles = $azar->shuffleArray($posibles);
            usort($posibles, fn ($a, $b) => ($usos[$a['receta']->id] ?? 0) <=> ($usos[$b['receta']->id] ?? 0));
            $par = $this->elegirPar($posibles, $objetivo);

            if ($par === null) {
                $avisos['recetas'] = 'Con lo que no comes hay muy pocas recetas para toda la semana: hemos dejado huecos. Puedes crear tus propias recetas y volver a probar.';
                if (isset($posibles[0])) {
                    [$x, $r] = $this->unaSola($posibles, $objetivo);
                    $comidas[] = $this->comida($fecha, 'comida', $x, $r);
                    $hoy[] = $x['receta']->id;
                }
            } else {
                foreach (['comida', 'cena'] as $i => $momento) {
                    $comidas[] = $this->comida($fecha, $momento, $par[$i][0], $par[$i][1]);
                    $hoy[] = $par[$i][0]['receta']->id;
                }
            }

            foreach ($hoy as $id) {
                $usos[$id] = ($usos[$id] ?? 0) + 1;
            }
            $ayer = $hoy;
        }

        $ajustadas = $this->ajustarAlPresupuesto($comidas, $recetas->reject($esDesayuno)->values()->all(), $u);
        $presupuesto = (float) $u->presupuesto_semanal;
        $masBarata = round(array_sum(array_column($ajustadas, 'coste')) + 1e-9, 2);

        // Si ni abaratando se llega al presupuesto, no se sacrifican calorías para nada: se queda la semana original.
        if ($comidas !== [] && $masBarata > $presupuesto) {
            $avisos['presupuesto'] = sprintf(
                'Con lo que no comes, ni la semana más barata que hemos encontrado (%s €) entra en tu presupuesto de %s €, así que te proponemos la que mejor cuadra tus calorías.',
                self::euros($masBarata), self::euros($presupuesto),
            );
        } else {
            $comidas = $ajustadas;
        }
        $coste = round(array_sum(array_column($comidas, 'coste')) + 1e-9, 2);

        return ['comidas' => $comidas, 'coste' => $coste, 'aviso' => $avisos === [] ? null : implode(' ', $avisos)];
    }

    /** Sustituye las comidas de esa semana por las propuestas. Una propuesta vacía no toca nada. */
    public function aplicar(User $u, CarbonImmutable $lunes, array $semana): void
    {
        if ($semana['comidas'] === []) {
            return;
        }

        DB::transaction(function () use ($u, $lunes, $semana) {
            $u->comidasPlan()->whereBetween('fecha', [$lunes->toDateString(), $lunes->addDays(6)->toDateString()])->delete();
            foreach ($semana['comidas'] as $c) {
                $u->comidasPlan()->create(['fecha' => $c['fecha'], 'momento' => $c['momento'], 'tipo' => 'receta', 'receta_id' => $c['receta_id'], 'raciones' => $c['raciones']]);
            }
        });
    }

    /**
     * Comida y cena del día: la primera pareja (en orden aleatorio) que con alguna combinación de raciones queda
     * a ±10 % del objetivo; si ninguna, la que más se acerca.
     *
     * @return array{0: array{0: array, 1: float}, 1: array{0: array, 1: float}}|null
     */
    private function elegirPar(array $posibles, float $objetivo): ?array
    {
        $mejor = null;
        $error = INF;

        foreach ($posibles as $i => $a) {
            foreach ($posibles as $j => $b) {
                if ($i === $j) {
                    continue;
                }
                foreach (self::RACIONES as $ra) {
                    foreach (self::RACIONES as $rb) {
                        $e = abs($a['kcal'] * $ra + $b['kcal'] * $rb - $objetivo);
                        if ($e < $error) {
                            [$mejor, $error] = [[[$a, $ra], [$b, $rb]], $e];
                        }
                    }
                }
                if ($error <= $objetivo * self::MARGEN) {
                    return $mejor;
                }
            }
        }

        return $mejor;
    }

    /** @return array{0: array, 1: float} La receta y raciones que más se acercan al objetivo, cuando solo hay una. */
    private function unaSola(array $posibles, float $objetivo): array
    {
        $mejor = [$posibles[0], 1];
        foreach ($posibles as $x) {
            foreach (self::RACIONES as $r) {
                if (abs($x['kcal'] * $r - $objetivo) < abs($mejor[0]['kcal'] * $mejor[1] - $objetivo)) {
                    $mejor = [$x, $r];
                }
            }
        }

        return $mejor;
    }

    /**
     * Mientras se pase del presupuesto: cambia la comida más cara por la receta más barata que pueda ir ese día
     * (sin repetir más de dos veces ni en días seguidos) y que deje el día a ±15 % del objetivo de calorías
     * (o, si el día ya estaba fuera, no más lejos que antes).
     */
    private function ajustarAlPresupuesto(array $comidas, array $principales, User $u): array
    {
        $presupuesto = (float) $u->presupuesto_semanal;
        $probadas = [];

        while (array_sum(array_column($comidas, 'coste')) > $presupuesto) {
            $caras = array_filter(array_keys($comidas), fn ($k) => $comidas[$k]['momento'] !== 'desayuno' && ! isset($probadas[$k]));
            if ($caras === []) {
                break;
            }
            usort($caras, fn ($a, $b) => $comidas[$b]['coste'] <=> $comidas[$a]['coste']);
            $k = $caras[0];
            $probadas[$k] = true;
            $actual = $comidas[$k];
            $fecha = CarbonImmutable::parse($actual['fecha']);
            $entreSemana = $fecha->isWeekday();

            $ocupadas = fn (string $dia) => array_column(array_filter($comidas, fn ($c, $i) => $i !== $k && $c['fecha'] === $dia && $c['momento'] !== 'desayuno', ARRAY_FILTER_USE_BOTH), 'receta_id');
            $prohibidas = [...$ocupadas($actual['fecha']), ...$ocupadas($fecha->subDay()->toDateString()), ...$ocupadas($fecha->addDay()->toDateString())];
            $usos = array_count_values(array_column(array_filter($comidas, fn ($c, $i) => $i !== $k && $c['momento'] !== 'desayuno', ARRAY_FILTER_USE_BOTH), 'receta_id'));

            $objetivo = (float) $u->objetivo_kcal;
            $restoDia = array_sum(array_column(array_filter($comidas, fn ($c, $i) => $i !== $k && $c['fecha'] === $actual['fecha'], ARRAY_FILTER_USE_BOTH), 'kcal'));
            $errorActual = abs($restoDia + $actual['kcal'] - $objetivo);
            $admisible = fn (float $kcal) => abs($restoDia + $kcal - $objetivo) <= max($objetivo * 0.15, $errorActual);

            $mejor = null;
            foreach ($principales as $x) {
                $id = $x['receta']->id;
                if (in_array($id, $prohibidas, true) || ($usos[$id] ?? 0) >= 2 || ! $x[$entreSemana ? 'entre_semana' : 'finde']) {
                    continue;
                }
                foreach (self::RACIONES as $r) {
                    // Comparado ya redondeado: si no, una sustituta «0,996 €» por una de 1,00 € se cambiaría sin fin.
                    $coste = round($x['coste'] * $r + 1e-9, 2);
                    if ($admisible($x['kcal'] * $r) && $coste < $actual['coste'] && ($mejor === null || $coste < round($mejor[0]['coste'] * $mejor[1] + 1e-9, 2))) {
                        $mejor = [$x, $r];
                    }
                }
            }

            if ($mejor !== null) {
                $comidas[$k] = $this->comida($fecha, $actual['momento'], $mejor[0], $mejor[1]);
                unset($probadas[$k]);
            }
        }

        return $comidas;
    }

    private static function euros(float $n): string
    {
        return number_format($n, 2, ',', '.');
    }

    private function comida(CarbonImmutable $fecha, string $momento, array $x, float $raciones): array
    {
        return [
            'fecha' => $fecha->toDateString(),
            'momento' => $momento,
            'receta_id' => $x['receta']->id,
            'titulo' => $x['receta']->titulo,
            'raciones' => $raciones,
            'kcal' => round($x['kcal'] * $raciones, 1),
            'coste' => round($x['coste'] * $raciones + 1e-9, 2),
        ];
    }
}
