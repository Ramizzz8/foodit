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

        DB::transaction(function () use ($alimentos) {
            foreach (require database_path('data/recetas.php') as $datos) {
                $receta = Receta::updateOrCreate(
                    ['user_id' => null, 'slug' => Str::slug($datos['titulo'])],
                    [
                        'titulo' => $datos['titulo'],
                        'descripcion' => $datos['descripcion'] ?? null,
                        'raciones' => $datos['raciones'],
                        'minutos' => $datos['minutos'],
                        'dificultad' => $datos['dificultad'],
                        'etiquetas' => $datos['etiquetas'],
                        'pasos' => $datos['pasos'],
                        'foto_url' => $datos['foto_url'] ?? null,
                        'foto_credito' => $datos['foto_credito'] ?? null,
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
