<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega el módulo "Usuarios y Roles" (Administrador) para la Fase 2.
     * Idempotente.
     */
    public function up(): void
    {
        if (! DB::table('modulo')->where('id_modulo', 13)->exists()) {
            DB::table('modulo')->insert([
                'id_modulo' => 13,
                'nombre' => 'Usuarios y Roles',
                'url' => '/home/usuarios',
                'url_foco' => 'usuarios',
                'url_activa' => '1',
                'icon' => 'ManageAccounts',
                'estado' => '1',
                'id_modulo_parent' => 1,
                'orden' => 1006,
            ]);
        }

        if (! DB::table('rol_modulo')->where('id_rol', 1)->where('id_modulo', 13)->exists()) {
            DB::table('rol_modulo')->insert([
                'id_rol' => 1,
                'id_modulo' => 13,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('rol_modulo')->where('id_modulo', 13)->delete();
        DB::table('modulo')->where('id_modulo', 13)->delete();
    }
};
