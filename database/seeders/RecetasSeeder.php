<?php

namespace Database\Seeders;

use App\Models\Alimento;
use App\Models\Receta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Recetas de Foodit (visibles para todos). Necesita AlimentosSeeder. Se puede repetir sin duplicar. */
class RecetasSeeder extends Seeder
{
    public function run(): void
    {
        $alimentos = Alimento::pluck('id', 'nombre');
        $fotos = (require database_path('data/fotos.php'))['platos'];

        DB::transaction(function () use ($alimentos, $fotos) {
            foreach (require database_path('data/recetas.php') as $datos) {
                $slug = Str::slug($datos['titulo']);
                $receta = Receta::updateOrCreate(
                    ['user_id' => null, 'slug' => $slug],
                    [
                        'titulo' => $datos['titulo'],
                        'descripcion' => $datos['descripcion'] ?? null,
                        'raciones' => $datos['raciones'],
                        'minutos' => $datos['minutos'],
                        'dificultad' => $datos['dificultad'],
                        'etiquetas' => $datos['etiquetas'],
                        'pasos' => $datos['pasos'],
                        'foto_url' => isset($fotos[$slug]) ? "/images/platos/{$slug}.webp" : null,
                        'foto_credito' => isset($fotos[$slug]) ? "{$fotos[$slug]['autor']} · {$fotos[$slug]['licencia']}" : null,
                    ],
                );

                $receta->ingredientes()->delete();
                foreach ($datos['ingredientes'] as $orden => $i) {
                    [$cantidad, $unidad, $alimento, $texto] = $i;
                    $receta->ingredientes()->create([
                        'alimento_id' => $alimentos[$alimento] ?? throw new RuntimeException("Alimento desconocido en «{$datos['titulo']}»: {$alimento}"),
                        'texto' => $texto,
                        'cantidad' => $cantidad,
                        'unidad' => $unidad,
                        'nota' => $i[4] ?? null,
                        'orden' => $orden,
                    ]);
                }
            }
        });
    }
}
