<?php

namespace Database\Factories;

use App\Models\Receta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Receta> */
class RecetaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'titulo' => $t = fake()->sentence(3),
            'slug' => Str::slug($t),
            'raciones' => 2,
            'minutos' => 30,
            'dificultad' => 'facil',
            'etiquetas' => [],
            'pasos' => ['Paso único.'],
        ];
    }
}
