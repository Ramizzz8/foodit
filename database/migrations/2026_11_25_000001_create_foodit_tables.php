<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedInteger('objetivo_kcal')->default(2000);
            $t->decimal('presupuesto_semanal', 6, 2)->default(60);
            $t->boolean('planifica_desayuno')->default(false);
            $t->boolean('es_demo')->default(false)->index();
            $t->dateTime('caduca_en')->nullable()->index();
        });

        // Catálogo de Mercadona (copia nocturna).
        Schema::create('productos_mercadona', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('nombre');
            $t->string('envase')->nullable();
            $t->float('cantidad_envase');
            $t->enum('unidad_envase', ['g', 'ml', 'ud']);
            $t->decimal('precio', 8, 2);
            $t->decimal('precio_referencia', 8, 3)->nullable();
            $t->string('formato_referencia')->nullable();
            $t->boolean('aproximado')->default(false);
            $t->string('categoria');
            $t->string('subcategoria');
            $t->unsignedInteger('orden_pasillo')->default(0);
            $t->string('foto_url')->nullable();
            $t->dateTime('visto_en');
            $t->timestamps();
            $t->index('nombre');
        });

        Schema::create('precios_historicos', function (Blueprint $t) {
            $t->id();
            $t->string('producto_mercadona_id')->index();
            $t->decimal('precio', 8, 2);
            $t->date('fecha');
        });

        Schema::create('sincronizaciones', function (Blueprint $t) {
            $t->id();
            $t->dateTime('empezada_en');
            $t->dateTime('terminada_en')->nullable();
            $t->enum('estado', ['en_curso', 'ok', 'error'])->default('en_curso');
            $t->unsignedInteger('productos')->default(0);
            $t->text('error')->nullable();
        });

        // Alimentos genéricos: el puente entre recetas, precios y calorías.
        Schema::create('alimentos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('slug')->unique();
            $t->float('kcal')->nullable();
            $t->float('proteinas')->nullable();
            $t->float('hidratos')->nullable();
            $t->float('grasas')->nullable();
            $t->string('fuente')->nullable();
            $t->float('gramos_por_ml')->nullable();
            $t->float('gramos_por_unidad')->nullable();
            $t->string('producto_mercadona_id')->nullable();
            $t->timestamps();
        });

        Schema::create('alimento_producto_usuario', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('alimento_id')->constrained()->cascadeOnDelete();
            $t->string('producto_mercadona_id');
            $t->unique(['user_id', 'alimento_id']);
        });

        Schema::create('recetas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('receta_origen_id')->nullable()->constrained('recetas')->nullOnDelete();
            $t->string('titulo');
            $t->string('slug');
            $t->text('descripcion')->nullable();
            $t->unsignedInteger('raciones')->default(2);
            $t->unsignedInteger('minutos')->nullable();
            $t->enum('dificultad', ['facil', 'media', 'dificil'])->default('facil');
            $t->json('etiquetas');
            $t->json('pasos');
            $t->string('foto_path')->nullable();
            $t->string('foto_url')->nullable();
            $t->string('foto_credito')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'slug']);
        });

        Schema::create('ingredientes_receta', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $t->foreignId('alimento_id')->nullable()->constrained()->nullOnDelete();
            $t->string('texto');
            $t->float('cantidad')->nullable();
            $t->enum('unidad', ['g', 'kg', 'ml', 'l', 'ud', 'cda', 'cdta', 'pizca', 'al_gusto']);
            $t->string('nota')->nullable();
            $t->unsignedInteger('orden')->default(0);
        });

        Schema::create('recetas_guardadas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'receta_id']);
        });

        Schema::create('comidas_plan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('fecha');
            $t->enum('momento', ['desayuno', 'comida', 'cena']);
            $t->enum('tipo', ['receta', 'fuera', 'sobras'])->default('receta');
            $t->foreignId('receta_id')->nullable()->constrained('recetas')->nullOnDelete();
            $t->float('raciones')->default(1);
            $t->string('nota')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'fecha', 'momento']);
        });

        Schema::create('lista_marcas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('lunes');
            $t->foreignId('alimento_id')->constrained()->cascadeOnDelete();
            $t->boolean('tachado')->default(false);
            $t->unique(['user_id', 'lunes', 'alimento_id']);
        });

        Schema::create('lista_extras', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('lunes');
            $t->string('texto');
            $t->boolean('tachado')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['lista_extras', 'lista_marcas', 'comidas_plan', 'recetas_guardadas', 'ingredientes_receta', 'recetas',
            'alimento_producto_usuario', 'alimentos', 'sincronizaciones', 'precios_historicos', 'productos_mercadona'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['objetivo_kcal', 'presupuesto_semanal', 'planifica_desayuno', 'es_demo', 'caduca_en']));
    }
};
