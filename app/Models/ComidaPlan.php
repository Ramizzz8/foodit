<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComidaPlan extends Model
{
    use HasFactory;

    protected $table = 'comidas_plan';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['raciones' => 'float'];
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    /** Se guarda como «Y-m-d» (sin hora) para que las búsquedas por fecha exacta funcionen. */
    protected function fecha(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v === null ? null : CarbonImmutable::parse($v)->startOfDay(),
            set: fn ($v) => $v === null ? null : CarbonImmutable::parse($v)->toDateString(),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
