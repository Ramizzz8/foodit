<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Producto del catálogo de Mercadona (copia nocturna). El id es el de Mercadona. */
class ProductoMercadona extends Model
{
    use HasFactory;

    protected $table = 'productos_mercadona';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['cantidad_envase' => 'float', 'precio' => 'float', 'precio_referencia' => 'float', 'aproximado' => 'boolean', 'visto_en' => 'datetime'];
    }

    public function historico(): HasMany
    {
        return $this->hasMany(PrecioHistorico::class);
    }
}
