<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListaMarca extends Model
{
    use HasFactory;

    protected $table = 'lista_marcas';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['lunes' => 'date', 'tachado' => 'boolean'];
    }
}
