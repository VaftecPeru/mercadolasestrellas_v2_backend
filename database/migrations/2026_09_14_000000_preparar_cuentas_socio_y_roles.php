<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cambios aditivos e idempotentes para la Fase 1 de autenticación de socios:
     * - usuarios.id_usuario pasa a AUTO_INCREMENT (la BD real no lo tenía).
     * - usuarios: nuevos campos debe_cambiar_password, bloqueado, ultimo_acceso.
     * - Se elimina usuarios.rol (id_rol pasa a ser la única fuente de verdad).
     * - Se asegura el rol Cajero (id_rol = 3) y sus permisos en rol_modulo.
     * - Se mapea Socio -> Reporte Deudas en rol_modulo.
     */
    public function up(): void
    {
        // 0) Normalizar fechas inválidas previas (evita fallo del ALTER en modo estricto).
        DB::table('usuarios')
            ->where('fecha_registro', '0000-00-00 00:00:00')
            ->orWhereNull('fecha_registro')
            ->update(['fecha_registro' => now()]);

        // 1) AUTO_INCREMENT sobre la PK existente (idempotente).
        DB::statement('ALTER TABLE usuarios MODIFY id_usuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        // 2) Campos nuevos (idempotente).
        Schema::table('usuarios', function (Blueprint $table) {
            if (! Schema::hasColumn('usuarios', 'debe_cambiar_password')) {
                $table->boolean('debe_cambiar_password')->default(1)->after('estado');
            }
            if (! Schema::hasColumn('usuarios', 'bloqueado')) {
                $table->boolean('bloqueado')->default(0)->after('debe_cambiar_password');
            }
            if (! Schema::hasColumn('usuarios', 'ultimo_acceso')) {
                $table->dateTime('ultimo_acceso')->nullable()->after('bloqueado');
            }
        });

        // 3) Los usuarios existentes (Administradores) no requieren cambio de contraseña.
        DB::table('usuarios')->where('debe_cambiar_password', 1)->update(['debe_cambiar_password' => 0]);

        // 4) Eliminar usuarios.rol (id_rol es la única fuente de verdad).
        if (Schema::hasColumn('usuarios', 'rol')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('rol');
            });
        }

        // 5) Asegurar rol Cajero (id_rol = 3).
        if (! DB::table('rol')->where('id_rol', 3)->exists()) {
            DB::table('rol')->insert([
                'id_rol' => 3,
                'nombre' => 'Cajero',
                'codigo' => 'CAJ',
                'es_administrador' => '0',
            ]);
        }

        // 6) Permisos en rol_modulo (idempotente).
        $mapeoRoles = [
            2 => [2, 9], // Socio: Reportes (padre) + Reporte Deudas
            3 => [1, 2, 3, 7, 8, 9, 10, 11, 12], // Cajero: Panel, Reportes, Socios, Pagos y reportes
        ];

        foreach ($mapeoRoles as $idRol => $modulos) {
            foreach ($modulos as $idModulo) {
                if (! DB::table('rol_modulo')->where('id_rol', $idRol)->where('id_modulo', $idModulo)->exists()) {
                    DB::table('rol_modulo')->insert([
                        'id_rol' => $idRol,
                        'id_modulo' => $idModulo,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir rol_modulo agregado (solo los mapeos creados aquí).
        DB::table('rol_modulo')->where('id_rol', 2)->delete();
        DB::table('rol_modulo')->where('id_rol', 3)->delete();

        // Revertir rol Cajero.
        DB::table('rol')->where('id_rol', 3)->delete();

        // Restaurar columna rol.
        if (! Schema::hasColumn('usuarios', 'rol')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('rol')->after('id_usuario');
            });
        }

        // Quitar campos nuevos.
        Schema::table('usuarios', function (Blueprint $table) {
            $columns = ['debe_cambiar_password', 'bloqueado', 'ultimo_acceso'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('usuarios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
