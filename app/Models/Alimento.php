<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Alimento genérico («tomate», «arroz redondo»): nutrición por 100 g, conversiones y producto de Mercadona. */
class Alimento extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $attributes = ['grupo' => 'vegetal', 'alergenos' => '[]'];

    protected function casts(): array
    {
        return ['kcal' => 'float', 'proteinas' => 'float', 'hidratos' => 'float', 'grasas' => 'float', 'gramos_por_ml' => 'float', 'gramos_por_unidad' => 'float', 'alergenos' => 'array'];
    }

    /** El producto que ha elegido el usuario para este alimento o, si no, el de por defecto. */
    public function productoPara(?User $u): ?ProductoMercadona
    {
        $elegido = $u ? AlimentoProductoUsuario::where('user_id', $u->id)->where('alimento_id', $this->id)->value('producto_mercadona_id') : null;

        return $elegido ? ProductoMercadona::find($elegido) : $this->producto;
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoMercadona::class, 'producto_mercadona_id');
    }
}
