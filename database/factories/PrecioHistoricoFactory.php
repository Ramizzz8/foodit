<?php

namespace Database\Factories;

use App\Models\PrecioHistorico;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PrecioHistorico> */
class PrecioHistoricoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'producto_mercadona_id' => '1',
            'precio' => 1.0,
            'fecha' => now()->toDateString(),
        ];
    }
}
