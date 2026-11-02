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
     * Crea de forma idempotente las tablas base presentes en mercado_bd.sql
     * que no contaban con definición en las migraciones de Laravel.
     */
    public function up(): void
    {
        // 1. personas
        if (! Schema::hasTable('personas')) {
            Schema::create('personas', function (Blueprint $table) {
                $table->id('id_persona');
                $table->string('nombre', 255);
                $table->string('apellido_paterno', 255);
                $table->string('apellido_materno', 255);
                $table->string('dni', 255);
                $table->string('correo', 255)->nullable();
                $table->string('telefono', 255)->nullable();
                $table->string('estado', 255)->default('1');
                $table->dateTime('fecha_registro')->useCurrent();
                $table->string('nombre_completo', 450)->nullable();
                $table->string('direccion', 500)->nullable();
                $table->string('sexo', 50)->default('1');
                $table->string('tipo_persona', 50)->nullable()->default('1');
            });
        }

        // 2. rol
        if (! Schema::hasTable('rol')) {
            Schema::create('rol', function (Blueprint $table) {
                $table->id('id_rol');
                $table->string('nombre', 100);
                $table->string('codigo', 100)->nullable();
                $table->string('es_administrador', 1)->nullable()->default('0');
            });
        }

        // Población inicial de roles si la tabla está vacía
        if (Schema::hasTable('rol') && DB::table('rol')->count() === 0) {
            DB::table('rol')->insert([
                ['id_rol' => 1, 'nombre' => 'Administrador', 'codigo' => 'ADM', 'es_administrador' => '1'],
                ['id_rol' => 2, 'nombre' => 'Socio', 'codigo' => 'SOC', 'es_administrador' => '0'],
                ['id_rol' => 3, 'nombre' => 'Cajero', 'codigo' => 'CAJ', 'es_administrador' => '0'],
            ]);
        }

        // 3. modulo
        if (! Schema::hasTable('modulo')) {
            Schema::create('modulo', function (Blueprint $table) {
                $table->id('id_modulo');
                $table->string('nombre', 100)->nullable();
                $table->string('url', 255)->nullable();
                $table->string('url_foco', 255)->nullable();
                $table->string('url_activa', 1)->nullable();
                $table->string('icon', 50)->nullable();
                $table->string('estado', 1)->nullable()->default('0');
                $table->unsignedBigInteger('id_modulo_parent')->nullable();
                $table->unsignedBigInteger('orden')->nullable();
            });
        }

        // Población inicial de módulos si la tabla está vacía
        if (Schema::hasTable('modulo') && DB::table('modulo')->count() === 0) {
            DB::table('modulo')->insert([
                ['id_modulo' => 1, 'nombre' => 'Panel de Control', 'url' => null, 'url_foco' => null, 'url_activa' => '0', 'icon' => 'DashboardIcon', 'estado' => '1', 'id_modulo_parent' => null, 'orden' => 1000],
                ['id_modulo' => 2, 'nombre' => 'Reportes', 'url' => null, 'url_foco' => null, 'url_activa' => '0', 'icon' => 'DashboardIcon', 'estado' => '1', 'id_modulo_parent' => null, 'orden' => 2000],
                ['id_modulo' => 3, 'nombre' => 'Socios', 'url' => '/home/socios', 'url_foco' => 'socios', 'url_activa' => '1', 'icon' => 'Groups', 'estado' => '1', 'id_modulo_parent' => 1, 'orden' => 1001],
                ['id_modulo' => 4, 'nombre' => 'Puestos', 'url' => '/home/puestos', 'url_foco' => 'puestos', 'url_activa' => '1', 'icon' => 'Storefront', 'estado' => '1', 'id_modulo_parent' => 1, 'orden' => 1002],
                ['id_modulo' => 5, 'nombre' => 'Servicios', 'url' => '/home/servicios', 'url_foco' => 'servicios', 'url_activa' => '1', 'icon' => 'ShoppingBasket', 'estado' => '1', 'id_modulo_parent' => 1, 'orden' => 1003],
                ['id_modulo' => 6, 'nombre' => 'Generar Cuota', 'url' => '/home/cuotas', 'url_foco' => 'cuotas', 'url_activa' => '1', 'icon' => 'Article', 'estado' => '1', 'id_modulo_parent' => 1, 'orden' => 1004],
                ['id_modulo' => 7, 'nombre' => 'Pagos', 'url' => '/home/pagos', 'url_foco' => 'pagos', 'url_activa' => '1', 'icon' => 'MonetizationOn', 'estado' => '1', 'id_modulo_parent' => 1, 'orden' => 1005],
                ['id_modulo' => 8, 'nombre' => 'Reporte Pagos', 'url' => '/home/reporte-pagos', 'url_foco' => 'reporte-pagos', 'url_activa' => '1', 'icon' => 'Description', 'estado' => '1', 'id_modulo_parent' => 2, 'orden' => 2001],
                ['id_modulo' => 9, 'nombre' => 'Reporte Deudas', 'url' => '/home/reporte-deudas', 'url_foco' => 'reporte-deudas', 'url_activa' => '1', 'icon' => 'Description', 'estado' => '1', 'id_modulo_parent' => 2, 'orden' => 2002],
                ['id_modulo' => 10, 'nombre' => 'Reporte de cuotas por metrado', 'url' => '/home/reporte-cuotas-metrado', 'url_foco' => 'reporte-cuotas-metrado', 'url_activa' => '1', 'icon' => 'Description', 'estado' => '1', 'id_modulo_parent' => 2, 'orden' => 2003],
                ['id_modulo' => 11, 'nombre' => 'Reporte de cuotas por puestos', 'url' => '/home/reporte-cuotas-puesto', 'url_foco' => 'reporte-cuotas-puesto', 'url_activa' => '1', 'icon' => 'Description', 'estado' => '1', 'id_modulo_parent' => 2, 'orden' => 2004],
                ['id_modulo' => 12, 'nombre' => 'Reporte resumen por puesto', 'url' => '/home/reporte-resumen-puesto', 'url_foco' => 'reporte-resumen-puesto', 'url_activa' => '1', 'icon' => 'Description', 'estado' => '1', 'id_modulo_parent' => 2, 'orden' => 2005],
            ]);
        }

        // 4. rol_modulo
        if (! Schema::hasTable('rol_modulo')) {
            Schema::create('rol_modulo', function (Blueprint $table) {
                $table->unsignedBigInteger('id_rol');
                $table->unsignedBigInteger('id_modulo');
                $table->primary(['id_rol', 'id_modulo']);
                $table->index('id_modulo', 'rolmodulo_modulo_fk');
            });
        }

        // 5. banco
        if (! Schema::hasTable('banco')) {
            Schema::create('banco', function (Blueprint $table) {
                $table->unsignedBigInteger('id_banco')->primary();
                $table->string('nombre', 255);
                $table->string('siglas', 20);
                $table->string('estado', 1)->default('1');
            });
        }

        // Población inicial de bancos si la tabla está vacía
        if (Schema::hasTable('banco') && DB::table('banco')->count() === 0) {
            DB::table('banco')->insert([
                ['id_banco' => 1, 'nombre' => 'Banco de Crédito del Perú', 'siglas' => 'BCP', 'estado' => '1'],
                ['id_banco' => 2, 'nombre' => 'Banco Continental BBVA', 'siglas' => 'BBVA', 'estado' => '1'],
                ['id_banco' => 3, 'nombre' => 'Interbank', 'siglas' => 'IBK', 'estado' => '1'],
                ['id_banco' => 4, 'nombre' => 'Banco Nacion', 'siglas' => 'BN', 'estado' => '1'],
                ['id_banco' => 5, 'nombre' => 'Scotiabank', 'siglas' => 'SC', 'estado' => '1'],
            ]);
        }

        // 6. banco_cuenta
        if (! Schema::hasTable('banco_cuenta')) {
            Schema::create('banco_cuenta', function (Blueprint $table) {
                $table->unsignedBigInteger('id_bancocuenta')->primary();
                $table->string('numero_cuenta', 100);
                $table->unsignedBigInteger('id_banco');
                $table->string('estado', 1)->default('1');
            });
        }

        // 7. pago_banco
        if (! Schema::hasTable('pago_banco')) {
            Schema::create('pago_banco', function (Blueprint $table) {
                $table->bigInteger('id_pagobanco')->primary();
                $table->unsignedBigInteger('id_banco');
                $table->unsignedBigInteger('id_bancocuenta');
                $table->string('numero_operacion', 50);
                $table->date('fecha_operacion');
            });
        }

        // 8. setup_anio
        if (! Schema::hasTable('setup_anio')) {
            Schema::create('setup_anio', function (Blueprint $table) {
                $table->unsignedBigInteger('id_anio')->primary();
                $table->string('nombre', 50);
            });
        }

        // Población de años si está vacía
        if (Schema::hasTable('setup_anio') && DB::table('setup_anio')->count() === 0) {
            $anios = [];
            for ($anio = 2012; $anio <= 2026; $anio++) {
                $anios[] = ['id_anio' => $anio, 'nombre' => (string) $anio];
            }
            DB::table('setup_anio')->insert($anios);
        }

        // 9. setup_mes
        if (! Schema::hasTable('setup_mes')) {
            Schema::create('setup_mes', function (Blueprint $table) {
                $table->unsignedBigInteger('id_mes')->primary();
                $table->string('nombre', 50);
            });
        }

        // Población de meses si está vacía
        if (Schema::hasTable('setup_mes') && DB::table('setup_mes')->count() === 0) {
            DB::table('setup_mes')->insert([
                ['id_mes' => 1, 'nombre' => 'Enero'],
                ['id_mes' => 2, 'nombre' => 'Febrero'],
                ['id_mes' => 3, 'nombre' => 'Marzo'],
                ['id_mes' => 4, 'nombre' => 'Abril'],
                ['id_mes' => 5, 'nombre' => 'Mayo'],
                ['id_mes' => 6, 'nombre' => 'Junio'],
                ['id_mes' => 7, 'nombre' => 'Julio'],
                ['id_mes' => 8, 'nombre' => 'Agosto'],
                ['id_mes' => 9, 'nombre' => 'Setiembre'],
                ['id_mes' => 10, 'nombre' => 'Octubre'],
                ['id_mes' => 11, 'nombre' => 'Noviembre'],
                ['id_mes' => 12, 'nombre' => 'Diciembre'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_banco');
        Schema::dropIfExists('banco_cuenta');
        Schema::dropIfExists('banco');
        Schema::dropIfExists('rol_modulo');
        Schema::dropIfExists('modulo');
        Schema::dropIfExists('rol');
        Schema::dropIfExists('setup_mes');
        Schema::dropIfExists('setup_anio');
        Schema::dropIfExists('personas');
    }
};

