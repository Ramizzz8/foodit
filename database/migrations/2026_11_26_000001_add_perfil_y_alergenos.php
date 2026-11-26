<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Alérgenos y grupo de cada alimento; perfil de la persona para el alta guiada y la semana generada. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alimentos', function (Blueprint $t) {
            $t->json('alergenos')->nullable();
            $t->string('grupo', 12)->default('vegetal');
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('sexo', 6)->nullable();
            $t->unsignedTinyInteger('edad')->nullable();
            $t->unsignedSmallInteger('altura_cm')->nullable();
            $t->decimal('peso_kg', 5, 1)->nullable();
            $t->string('actividad', 12)->nullable();
            $t->string('meta', 10)->nullable();
            $t->string('dieta', 12)->default('omnivora');
            $t->json('alergenos')->nullable();
            $t->json('alimentos_vetados')->nullable();
            $t->unsignedSmallInteger('minutos_entre_semana')->nullable();
            $t->dateTime('consentimiento_salud_en')->nullable();
            $t->dateTime('perfil_completo_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('alimentos', fn (Blueprint $t) => $t->dropColumn(['alergenos', 'grupo']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['sexo', 'edad', 'altura_cm', 'peso_kg', 'actividad', 'meta', 'dieta', 'alergenos', 'alimentos_vetados', 'minutos_entre_semana', 'consentimiento_salud_en', 'perfil_completo_en']));
    }
};
