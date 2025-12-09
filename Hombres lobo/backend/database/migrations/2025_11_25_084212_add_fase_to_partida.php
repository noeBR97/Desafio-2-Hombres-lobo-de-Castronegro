<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->string('fase_actual', 20)->default('noche');
            $table->unsignedInteger('ronda_actual')->default(1);
            $table->timestamp('fin_fase_at')->nullable();
            $table->string('ganador', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->dropColumn('fase_actual');
            $table->dropColumn('ronda_actual');
            $table->dropColumn('fin_fase_at');
            $table->dropColumn('ganador');
        });
    }
};
