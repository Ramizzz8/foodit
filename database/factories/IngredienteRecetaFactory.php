<?php

namespace Database\Factories;

use App\Models\Alimento;
use App\Models\IngredienteReceta;
use App\Models\Receta;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IngredienteReceta> */
class IngredienteRecetaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'receta_id' => Receta::factory(),
            'alimento_id' => Alimento::factory(),
            'texto' => 'ingrediente',
            'cantidad' => 100,
            'unidad' => 'g',
            'orden' => 0,
        ];
    }
}
