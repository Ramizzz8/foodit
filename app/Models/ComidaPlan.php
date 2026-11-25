<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComidaPlan extends Model
{
    use HasFactory;

    protected $table = 'comidas_plan';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'raciones' => 'float'];
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }
}
