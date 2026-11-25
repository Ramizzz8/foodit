<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlimentoProductoUsuario extends Model
{
    use HasFactory;

    protected $table = 'alimento_producto_usuario';

    public $timestamps = false;

    protected $guarded = [];
}
