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
    Schema::create('jugadores_partida', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_partida')->constrained('partidas')->onDelete('cascade');
        $table->foreignId('id_usuario')->nullable()->constrained('users')->onDelete('cascade');
        $table->boolean('es_bot')->default(false);
        $table->boolean('vivo')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jugadores_partida');
    }
};
