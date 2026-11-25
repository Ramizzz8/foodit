<?php

namespace Database\Factories;

use App\Models\Alimento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Alimento> */
class AlimentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $n = fake()->unique()->word(),
            'slug' => Str::slug($n).'-'.fake()->unique()->numberBetween(1, 999999),
            'kcal' => 100,
            'proteinas' => 5,
            'hidratos' => 10,
            'grasas' => 2,
            'fuente' => 'usda:0',
            'gramos_por_ml' => null,
            'gramos_por_unidad' => null,
            'producto_mercadona_id' => null,
        ];
    }
}
