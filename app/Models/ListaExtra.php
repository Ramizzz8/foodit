<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListaExtra extends Model
{
    use HasFactory;

    protected $table = 'lista_extras';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['lunes' => 'date', 'tachado' => 'boolean'];
    }
}
