<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrecioHistorico extends Model
{
    use HasFactory;

    protected $table = 'precios_historicos';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['precio' => 'float', 'fecha' => 'date'];
    }
}
