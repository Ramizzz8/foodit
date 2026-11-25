<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Alimento genérico («tomate», «arroz redondo»): nutrición por 100 g, conversiones y producto de Mercadona. */
class Alimento extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['kcal' => 'float', 'proteinas' => 'float', 'hidratos' => 'float', 'grasas' => 'float', 'gramos_por_ml' => 'float', 'gramos_por_unidad' => 'float'];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoMercadona::class, 'producto_mercadona_id');
    }
}
