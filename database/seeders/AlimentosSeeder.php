<?php

namespace Database\Seeders;

use App\Models\Alimento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Alimentos de la cocina española con nutrición y producto de Mercadona. Se puede repetir sin duplicar. */
class AlimentosSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('data/alimentos.php') as [$nombre, $kcal, $p, $h, $g, $usda, $gml, $gud, $producto, $grupo, $alergenos]) {
            Alimento::updateOrCreate(['slug' => Str::slug($nombre)], [
                'nombre' => $nombre,
                'kcal' => $kcal,
                'proteinas' => $p,
                'hidratos' => $h,
                'grasas' => $g,
                'fuente' => "usda:{$usda}",
                'gramos_por_ml' => $gml,
                'gramos_por_unidad' => $gud,
                'producto_mercadona_id' => $producto,
                'grupo' => $grupo,
                'alergenos' => $alergenos,
            ]);
        }
    }
}
