<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GiroNegocioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('giro_negocios')->count() === 0) {
            DB::table('giro_negocios')->insert([
                ['id_gironegocio' => 1, 'nombre' => 'Varios'],
            ]);
        }
    }
}
