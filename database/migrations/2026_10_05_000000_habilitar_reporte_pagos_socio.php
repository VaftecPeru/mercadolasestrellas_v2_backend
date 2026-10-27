<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existeRol = DB::table('rol')->where('id_rol', 2)->exists();
        $existeModulo = DB::table('modulo')->where('id_modulo', 8)->exists();

        if ($existeRol && $existeModulo &&
            ! DB::table('rol_modulo')->where('id_rol', 2)->where('id_modulo', 8)->exists()) {
            DB::table('rol_modulo')->insert([
                'id_rol' => 2,
                'id_modulo' => 8,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('rol_modulo')
            ->where('id_rol', 2)
            ->where('id_modulo', 8)
            ->delete();
    }
};
