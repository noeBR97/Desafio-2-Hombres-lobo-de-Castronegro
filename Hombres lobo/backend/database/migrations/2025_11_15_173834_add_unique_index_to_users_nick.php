<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // crea índice único si no existe
            if (!Schema::hasColumn('users', 'nick')) {
                // por si en algún entorno faltara la columna
                $table->string('nick')->after('email');
            }
            $table->unique('nick', 'users_nick_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_nick_unique');
        });
    }
};
