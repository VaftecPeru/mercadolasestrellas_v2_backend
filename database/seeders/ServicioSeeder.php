<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('servicios')->count() === 0) {
            $fecha = now();

            DB::table('servicios')->insert([
                ['id_servicio' => 1, 'nombre' => 'Gastos Administrativos', 'costo_unitario' => 0.00, 'tipo_servicio' => 1, 'activo' => '1', 'fecha_registro' => $fecha, 'codigo' => '1'],
                ['id_servicio' => 2, 'nombre' => 'Impuesto Predial y Arbitrios', 'costo_unitario' => 0.00, 'tipo_servicio' => 1, 'activo' => '1', 'fecha_registro' => $fecha, 'codigo' => '2'],
                ['id_servicio' => 3, 'nombre' => 'Servicio Contable', 'costo_unitario' => 0.00, 'tipo_servicio' => 1, 'activo' => '1', 'fecha_registro' => $fecha, 'codigo' => '3'],
                ['id_servicio' => 4, 'nombre' => 'Cuotas extraordinarias', 'costo_unitario' => 0.00, 'tipo_servicio' => 4, 'activo' => '1', 'fecha_registro' => $fecha, 'codigo' => '4'],
                ['id_servicio' => 5, 'nombre' => 'Multa por inasistencia', 'costo_unitario' => 0.00, 'tipo_servicio' => 2, 'activo' => '1', 'fecha_registro' => $fecha, 'codigo' => '5'],
                ['id_servicio' => 6, 'nombre' => 'Consumo de Agua', 'costo_unitario' => 0.00, 'tipo_servicio' => 1, 'activo' => '1', 'fecha_registro' => $fecha, 'codigo' => '6'],
                ['id_servicio' => 7, 'nombre' => 'Vigilancia y Seguridad', 'costo_unitario' => 0.00, 'tipo_servicio' => 1, 'activo' => '1', 'fecha_registro' => $fecha, 'codigo' => '7'],
                ['id_servicio' => 8, 'nombre' => 'Limpieza y Mantenimiento', 'costo_unitario' => 0.00, 'tipo_servicio' => 1, 'activo' => '1', 'fecha_registro' => $fecha, 'codigo' => '8'],
            ]);
        }
    }
}
