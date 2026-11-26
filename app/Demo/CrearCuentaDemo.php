<?php

namespace App\Demo;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Crea la copia temporal de la demo para un visitante. */
class CrearCuentaDemo
{
    public const HORAS = 24;

    public function __construct(private PlantillaDemo $plantilla) {}

    public function ejecutar(string $ip): User
    {
        return DB::transaction(function () {
            $u = new User([
                'name' => 'Lucía',
                'email' => 'demo-'.Str::uuid().'@foodit.test',
                'password' => Str::random(40),
                'objetivo_kcal' => 1600,
                'planifica_desayuno' => true,
                'presupuesto_semanal' => 60,
            ]);
            $u->forceFill(['es_demo' => true, 'caduca_en' => now()->addHours(self::HORAS), 'email_verified_at' => now(), 'perfil_completo_en' => now()])->save();

            $this->plantilla->aplicar($u, CarbonImmutable::today()->startOfWeek());

            return $u;
        });
    }
}
