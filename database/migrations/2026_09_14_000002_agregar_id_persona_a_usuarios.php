<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega usuarios.id_persona para vincular la información personal
     * (personas) con el usuario de Administrador/Cajero. Los socios siguen
     * usando la relación socios.id_socio -> personas.id_persona.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (! Schema::hasColumn('usuarios', 'id_persona')) {
                $table->unsignedBigInteger('id_persona')->nullable()->after('id_usuario');
            }
        });

        // Vincular los administradores existentes con su persona (mismo id).
        $usuarios = DB::table('usuarios')->whereNull('id_persona')->get();
        foreach ($usuarios as $usuario) {
            if (DB::table('personas')->where('id_persona', $usuario->id_usuario)->exists()) {
                DB::table('usuarios')
                    ->where('id_usuario', $usuario->id_usuario)
                    ->update(['id_persona' => $usuario->id_usuario]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'id_persona')) {
                $table->dropColumn('id_persona');
            }
        });
    }
};
