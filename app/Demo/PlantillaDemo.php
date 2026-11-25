<?php

namespace App\Demo;

use App\Models\Alimento;
use App\Models\Receta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Los datos de ejemplo de cada copia de la demo: recetario, semana planificada y lista empezada. */
class PlantillaDemo
{
    /** Recetas de Foodit que la persona de ejemplo tiene guardadas. */
    private const GUARDADAS = ['lentejas-estofadas', 'tortilla-de-patatas', 'salmon-al-horno-con-patatas', 'crema-de-calabacin', 'fajitas-de-pollo'];

    /** La semana: [día (0 = lunes), momento, receta (slug) | 'fuera' | 'sobras', raciones]. */
    private const SEMANA = [
        [0, 'desayuno', 'tostadas-con-tomate-y-aceite', 1], [1, 'desayuno', 'porridge-de-avena-con-platano', 1],
        [2, 'desayuno', 'yogur-con-fresas-y-nueces', 1], [3, 'desayuno', 'tostadas-con-tomate-y-aceite', 1],
        [4, 'desayuno', 'porridge-de-avena-con-platano', 1], [5, 'desayuno', 'tostas-de-aguacate-y-huevo', 1],
        [6, 'desayuno', 'yogur-con-fresas-y-nueces', 1],
        [0, 'comida', 'lentejas-estofadas', 1.5], [0, 'cena', 'crema-de-calabacin', 1],
        [1, 'comida', 'pollo-al-ajillo', 1.5], [1, 'cena', 'revuelto-de-champinones', 1],
        [2, 'comida', 'macarrones-con-tomate-y-chorizo', 1.5], [2, 'cena', 'ensalada-mixta', 1],
        [3, 'comida', 'garbanzos-con-espinacas', 1.5], [3, 'cena', 'merluza-en-salsa-verde', 1],
        [4, 'comida', 'arroz-con-verduras', 1.5], [4, 'cena', 'fuera', 1],
        [5, 'comida', 'paella-de-pollo-y-verduras', 1.5], [5, 'cena', 'ensalada-de-pasta', 1],
        [6, 'comida', 'sobras', 1.5], [6, 'cena', 'pisto-con-huevo', 1],
    ];

    /** Lo que ya tiene en casa (tachado en la lista). */
    private const TACHADOS = ['aceite de oliva virgen extra', 'pimentón dulce', 'laurel'];

    public function aplicar(User $u, CarbonImmutable $lunes): void
    {
        $deFoodit = Receta::whereNull('user_id')->pluck('id', 'slug');

        $u->recetasGuardadas()->sync($deFoodit->only(self::GUARDADAS)->values()->all());
        $this->recetasPropias($u);

        foreach (self::SEMANA as [$dia, $momento, $que, $raciones]) {
            $esReceta = ! in_array($que, ['fuera', 'sobras'], true);
            if ($esReceta && ! isset($deFoodit[$que])) {
                continue;
            }
            $u->comidasPlan()->create([
                'fecha' => $lunes->addDays($dia)->toDateString(),
                'momento' => $momento,
                'tipo' => $esReceta ? 'receta' : $que,
                'receta_id' => $esReceta ? $deFoodit[$que] : null,
                'raciones' => $raciones,
            ]);
        }

        foreach (Alimento::whereIn('nombre', self::TACHADOS)->pluck('id') as $alimento) {
            $u->listaMarcas()->create(['lunes' => $lunes->toDateString(), 'alimento_id' => $alimento, 'tachado' => true]);
        }
        $u->listaExtras()->create(['lunes' => $lunes->toDateString(), 'texto' => 'Papel de cocina']);
    }

    private function recetasPropias(User $u): void
    {
        $alimentos = Alimento::pluck('id', 'nombre');
        $recetas = [
            [
                'titulo' => 'Mi ensalada de garbanzos del táper',
                'descripcion' => 'Mi comida de oficina de los martes.',
                'raciones' => 2, 'minutos' => 10, 'etiquetas' => ['legumbre', 'rápida', 'vegetariana'],
                'pasos' => ['Escurre los garbanzos cocidos.', 'Mezcla con el tomate, el pimiento y la cebolla picados y aliña.'],
                'ingredientes' => [[295, 'g', 'garbanzos cocidos', '1 bote de garbanzos cocidos'], [1, 'ud', 'tomate', '1 tomate'],
                    [0.5, 'ud', 'pimiento rojo', '½ pimiento rojo'], [0.5, 'ud', 'cebolla', '½ cebolla'], [2, 'cda', 'aceite de oliva virgen extra', '2 cucharadas de aceite']],
            ],
            [
                'titulo' => 'Tostada de la casa',
                'descripcion' => 'Pan, tomate, aceite y jamón: el desayuno del domingo.',
                'raciones' => 1, 'minutos' => 5, 'etiquetas' => ['desayuno', 'rápida'],
                'pasos' => ['Tuesta el pan.', 'Ralla el tomate, aliña con aceite y pon el jamón encima.'],
                'ingredientes' => [[80, 'g', 'pan de barra', '80 g de pan'], [1, 'ud', 'tomate', '1 tomate'],
                    [1, 'cda', 'aceite de oliva virgen extra', '1 cucharada de aceite'], [30, 'g', 'jamón serrano', '30 g de jamón serrano']],
            ],
        ];

        foreach ($recetas as $datos) {
            $r = $u->recetas()->create([
                ...collect($datos)->only('titulo', 'descripcion', 'raciones', 'minutos', 'etiquetas', 'pasos')->all(),
                'slug' => Str::slug($datos['titulo']),
                'dificultad' => 'facil',
            ]);
            foreach ($datos['ingredientes'] as $orden => [$cantidad, $unidad, $alimento, $texto]) {
                $r->ingredientes()->create(['alimento_id' => $alimentos[$alimento] ?? null, 'cantidad' => $cantidad, 'unidad' => $unidad, 'texto' => $texto, 'orden' => $orden]);
            }
        }
    }
}
