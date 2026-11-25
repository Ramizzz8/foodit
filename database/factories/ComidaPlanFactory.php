<?php

namespace Database\Factories;

use App\Models\ComidaPlan;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ComidaPlan> */
class ComidaPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fecha' => now()->startOfWeek()->toDateString(),
            'momento' => 'comida',
            'tipo' => 'receta',
            'receta_id' => Receta::factory(),
            'raciones' => 1,
        ];
    }
}
