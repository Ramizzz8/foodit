<?php

namespace Database\Factories;

use App\Models\IngredienteReceta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<IngredienteReceta> */
class IngredienteRecetaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'receta_id' => \App\Models\Receta::factory(),
            'alimento_id' => \App\Models\Alimento::factory(),
            'texto' => 'ingrediente',
            'cantidad' => 100,
            'unidad' => 'g',
            'orden' => 0,
        ];
    }
}
