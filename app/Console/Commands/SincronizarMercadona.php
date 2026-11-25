<?php

namespace App\Console\Commands;

use App\Mercadona\SincronizarCatalogo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foodit:mercadona')]
#[Description('Copia el catálogo de alimentación de Mercadona (precios y envases)')]
class SincronizarMercadona extends Command
{
    public function handle(SincronizarCatalogo $sincronizar): int
    {
        $s = $sincronizar->ejecutar();

        if ($s->estado !== 'ok') {
            $this->error("La sincronización ha fallado: {$s->error}");

            return self::FAILURE;
        }

        $this->info("Catálogo actualizado: {$s->productos} productos.");

        return self::SUCCESS;
    }
}
