<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * Por seguridad no se insertan credenciales predeterminadas en los seeders.
     * El administrador inicial se aprovisiona mediante: php artisan admin:inicial
     */
    public function run(): void
    {
        // El acceso inicial se crea exclusivamente mediante el comando interactivo:
        // php artisan admin:inicial
    }
}
