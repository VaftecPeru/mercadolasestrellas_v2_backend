<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('documentos')->count() === 0) {
            DB::table('documentos')->insert([
                [
                    'id_documento' => 1,
                    'numero_documento' => 0,
                    'serie' => '0000',
                    'estado' => '1',
                    'fecha_registro' => now(),
                ],
            ]);
        }
    }
}
