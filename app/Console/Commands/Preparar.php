<?php

namespace App\Console\Commands;

use App\Models\ProductoMercadona;
use Database\Seeders\AlimentosSeeder;
use Database\Seeders\RecetasSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foodit:preparar')]
#[Description('Migra la base de datos, carga alimentos y recetas y, si no hay catálogo, lo descarga')]
class Preparar extends Command
{
    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => AlimentosSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => RecetasSeeder::class, '--force' => true]);

        if (ProductoMercadona::count() === 0) {
            $this->call('foodit:mercadona');
        }

        return self::SUCCESS;
    }
}
