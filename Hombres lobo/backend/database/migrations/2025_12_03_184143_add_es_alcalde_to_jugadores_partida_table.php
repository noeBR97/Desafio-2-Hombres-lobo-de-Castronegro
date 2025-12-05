<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jugadores_partida', function (Blueprint $table) {
            if (!Schema::hasColumn('jugadores_partida', 'es_alcalde')) {
                $table->boolean('es_alcalde')
                      ->default(false)
                      ->after('vivo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jugadores_partida', function (Blueprint $table) {
            if (Schema::hasColumn('jugadores_partida', 'es_alcalde')) {
                $table->dropColumn('es_alcalde');
            }
        });
    }
};
