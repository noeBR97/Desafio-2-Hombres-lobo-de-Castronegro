<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre')->after('id');
            $table->string('apellido1')->after('nombre');
            $table->string('apellido2')->nullable()->after('apellido1');

            $table->string('correo')->unique()->change();

            $table->string('nick')->unique()->after('correo');

            $table->unsignedInteger('partidas_jugadas')->default(0)->after('remember_token');
            $table->unsignedInteger('partidas_ganadas')->default(0)->after('partidas_jugadas');
            $table->unsignedInteger('partidas_perdidas')->default(0)->after('partidas_ganadas');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nombre', 'apellido1', 'apellido2', 'nick',
                'partidas_jugadas', 'partidas_ganadas', 'partidas_perdidas',
            ]);
        });
    }
};
