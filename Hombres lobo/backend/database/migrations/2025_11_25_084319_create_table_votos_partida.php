<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('votos_partida', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_partida');
            $table->unsignedBigInteger('id_jugador');
            $table->unsignedBigInteger('id_objetivo')->nullable();
            $table->string('tipo_fase', 10);
            $table->unsignedInteger('ronda');
            $table->timestamps();

            $table->foreign('id_partida')->references('id')->on('partidas')->onDelete('cascade');
            $table->foreign('id_jugador')->references('id')->on('jugadores_partida')->onDelete('cascade');
            $table->foreign('id_objetivo')->references('id')->on('jugadores_partida')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votos_partida');
    }
};
