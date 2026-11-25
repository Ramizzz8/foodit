<?php

namespace App\Console\Commands;

use App\Demo\LimpiarCuentasDemo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foodit:limpiar-demo')]
#[Description('Borra las copias de la demo caducadas (y las más antiguas si hay demasiadas)')]
class LimpiarDemo extends Command
{
    public function handle(LimpiarCuentasDemo $limpiar): int
    {
        $this->info('Copias de la demo borradas: '.$limpiar->ejecutar());

        return self::SUCCESS;
    }
}
