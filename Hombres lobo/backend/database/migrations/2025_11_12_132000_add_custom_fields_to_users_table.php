<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'name')) {
                $table->renameColumn('name', 'nombre');
            }

            if (Schema::hasColumn('users', 'email')) {
                $table->renameColumn('email', 'correo');
            }

            if (Schema::hasColumn('users', 'password')) {
                $table->renameColumn('password', 'clave');
            }

            if (!Schema::hasColumn('users', 'apellido1')) {
                $table->string('apellido1')->after('nombre');
            }

            if (!Schema::hasColumn('users', 'apellido2')) {
                $table->string('apellido2')->nullable()->after('apellido1');
            }

            if (!Schema::hasColumn('users', 'nick')) {
                $table->string('nick')->unique()->after('apellido2');
            } else {
                $table->unique('nick');
            }

            if (!Schema::hasColumn('users', 'rol_corp')) {
                $table->string('rol_corp')->default('usuario')->after('clave');
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'nombre')) {
                $table->renameColumn('nombre', 'name');
            }

            if (Schema::hasColumn('users', 'correo')) {
                $table->renameColumn('correo', 'email');
            }

            if (Schema::hasColumn('users', 'clave')) {
                $table->renameColumn('clave', 'password');
            }

            if (Schema::hasColumn('users', 'apellido1')) {
                $table->dropColumn('apellido1');
            }

            if (Schema::hasColumn('users', 'apellido2')) {
                $table->dropColumn('apellido2');
            }

            if (Schema::hasColumn('users', 'nick')) {
                $table->dropUnique(['nick']);
            }

            if (Schema::hasColumn('users', 'rol_corp')) {
                $table->dropColumn('rol_corp');
            }
        });
    }
};
