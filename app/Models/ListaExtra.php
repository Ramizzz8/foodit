<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListaExtra extends Model
{
    use HasFactory;

    protected $table = 'lista_extras';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tachado' => 'boolean'];
    }

    /** Se guarda como «Y-m-d» (sin hora) para que las búsquedas por fecha exacta funcionen. */
    protected function lunes(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v === null ? null : CarbonImmutable::parse($v)->startOfDay(),
            set: fn ($v) => $v === null ? null : CarbonImmutable::parse($v)->toDateString(),
        );
    }
}
