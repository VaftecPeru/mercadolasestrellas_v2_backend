<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's base catalogs.
     */
    public function run(): void
    {
        $this->call([
            BlockSeeder::class,
            GiroNegocioSeeder::class,
            DocumentoSeeder::class,
            ServicioSeeder::class,
        ]);
    }
}
