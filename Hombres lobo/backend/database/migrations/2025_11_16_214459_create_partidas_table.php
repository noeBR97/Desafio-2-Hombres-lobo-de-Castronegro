<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_partida');
            $table->string('estado')->default('en_espera');
            $table->unsignedBigInteger('id_creador_partida');
            $table->integer('numero_jugadores');
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamps();
            $table->foreign('id_creador_partida')
                  ->references('id')
                  ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partidas');
    }
};
