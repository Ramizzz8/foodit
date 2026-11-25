<?php

namespace Database\Factories;

use App\Models\ComidaPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ComidaPlan> */
class ComidaPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'fecha' => now()->startOfWeek()->toDateString(),
            'momento' => 'comida',
            'tipo' => 'receta',
            'receta_id' => \App\Models\Receta::factory(),
            'raciones' => 1,
        ];
    }
}
