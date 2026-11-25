<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sincronizacion extends Model
{
    use HasFactory;

    protected $table = 'sincronizaciones';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['empezada_en' => 'datetime', 'terminada_en' => 'datetime'];
    }
}
