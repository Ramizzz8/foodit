<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** La despensa: alimentos que cada persona tiene en casa (sin cantidades). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despensa', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('alimento_id')->constrained('alimentos')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'alimento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despensa');
    }
};
