<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('blocks')->count() === 0) {
            DB::table('blocks')->insert([
                ['id_block' => 1, 'nombre' => 'Ninguno'],
                ['id_block' => 2, 'nombre' => '1 Piso'],
                ['id_block' => 3, 'nombre' => '2 Piso'],
            ]);
        }
    }
}
