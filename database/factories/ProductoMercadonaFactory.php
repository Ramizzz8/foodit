<?php

namespace Database\Factories;

use App\Models\ProductoMercadona;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductoMercadona> */
class ProductoMercadonaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) fake()->unique()->numberBetween(1000, 999999),
            'nombre' => fake()->words(3, true),
            'envase' => 'Paquete',
            'cantidad_envase' => 1000,
            'unidad_envase' => 'g',
            'precio' => 1.50,
            'precio_referencia' => 1.50,
            'formato_referencia' => 'kg',
            'aproximado' => false,
            'categoria' => 'Arroz, legumbres y pasta',
            'subcategoria' => 'Arroz',
            'orden_pasillo' => 13,
            'foto_url' => null,
            'visto_en' => now(),
        ];
    }
}
