<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredienteReceta extends Model
{
    use HasFactory;

    protected $table = 'ingredientes_receta';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['cantidad' => 'float', 'orden' => 'integer'];
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    public function alimento(): BelongsTo
    {
        return $this->belongsTo(Alimento::class);
    }
}
