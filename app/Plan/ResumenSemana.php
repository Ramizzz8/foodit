<?php

namespace App\Plan;

use App\Cocina\CalculadoraReceta;
use App\Models\ComidaPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Totales de la semana del plan: kcal y macros por día frente al objetivo, y coste de lo que se come. */
class ResumenSemana
{
    /** Margen sobre el objetivo de calorías para considerar un día «dentro». */
    public const MARGEN = 0.10;

    public function __construct(private CalculadoraReceta $calculadora) {}

    /** Comidas de la semana con su receta cargada para calcular. */
    public static function comidas(User $u, CarbonImmutable $lunes): Collection
    {
        return $u->comidasPlan()
            ->whereBetween('fecha', [$lunes->toDateString(), $lunes->addDays(6)->toDateString()])
            ->with('receta.ingredientes.alimento.producto')
            ->get();
    }

    /**
     * @return array{dias: list<array{fecha: string, kcal: float, proteinas: float, hidratos: float, grasas: float, estado: 'dentro'|'encima'|'debajo'|'vacio'}>,
     *               coste: float, presupuesto: float, objetivo_kcal: int, completo: bool}
     */
    public function para(User $u, CarbonImmutable $lunes, ?Collection $comidas = null): array
    {
        $comidas ??= self::comidas($u, $lunes);
        $dias = [];
        $coste = 0.0;
        $completo = true;

        for ($d = 0; $d < 7; $d++) {
            $fecha = $lunes->addDays($d)->toDateString();
            $total = ['kcal' => 0.0, 'proteinas' => 0.0, 'hidratos' => 0.0, 'grasas' => 0.0];
            $conReceta = 0;

            foreach ($comidas->filter(fn (ComidaPlan $c) => $c->fecha->toDateString() === $fecha && $c->tipo === 'receta' && $c->receta) as $c) {
                $racion = $this->calculadora->porRacion($c->receta, $u);
                foreach ($total as $n => $_) {
                    $total[$n] += $racion[$n] * $c->raciones;
                }
                $coste += $racion['coste'] * $c->raciones;
                $completo = $completo && $racion['completo'];
                $conReceta++;
            }

            $dias[] = [
                'fecha' => $fecha,
                ...array_map(fn ($v) => round($v, 1), $total),
                'estado' => $conReceta === 0 ? 'vacio' : $this->estado($total['kcal'], $u->objetivo_kcal),
            ];
        }

        return [
            'dias' => $dias,
            'coste' => round($coste + 1e-9, 2),
            'presupuesto' => (float) $u->presupuesto_semanal,
            'objetivo_kcal' => $u->objetivo_kcal,
            'completo' => $completo,
        ];
    }

    private function estado(float $kcal, int $objetivo): string
    {
        return match (true) {
            $kcal > $objetivo * (1 + self::MARGEN) => 'encima',
            $kcal < $objetivo * (1 - self::MARGEN) => 'debajo',
            default => 'dentro',
        };
    }
}
