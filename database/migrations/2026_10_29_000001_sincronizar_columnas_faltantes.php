<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega de forma idempotente las columnas presentes en mercado_bd.sql
     * que faltaban en las definiciones de migraciones históricas.
     */
    public function up(): void
    {
        // 1. usuarios.id_rol
        if (Schema::hasTable('usuarios') && ! Schema::hasColumn('usuarios', 'id_rol')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->unsignedBigInteger('id_rol')->default(1)->after('id_usuario');
            });
        }

        // 2. socios.estado
        if (Schema::hasTable('socios') && ! Schema::hasColumn('socios', 'estado')) {
            Schema::table('socios', function (Blueprint $table) {
                $table->string('estado', 1)->default('1')->after('fecha_registro');
            });
        }

        // 3. cuotas.id_anio
        if (Schema::hasTable('cuotas') && ! Schema::hasColumn('cuotas', 'id_anio')) {
            Schema::table('cuotas', function (Blueprint $table) {
                $table->unsignedBigInteger('id_anio')->nullable()->after('global');
            });
        }

        // 4. detalle_pagos.id_servicio y detalle_pagos.id_deuda_cuota
        if (Schema::hasTable('detalle_pagos')) {
            Schema::table('detalle_pagos', function (Blueprint $table) {
                if (! Schema::hasColumn('detalle_pagos', 'id_servicio')) {
                    $table->bigInteger('id_servicio')->nullable()->after('importe');
                }
                if (! Schema::hasColumn('detalle_pagos', 'id_deuda_cuota')) {
                    $table->unsignedBigInteger('id_deuda_cuota')->nullable()->after('id_servicio');
                }
            });
        }

        // 5. deudas.id_cuota
        if (Schema::hasTable('deudas') && ! Schema::hasColumn('deudas', 'id_cuota')) {
            Schema::table('deudas', function (Blueprint $table) {
                $table->bigInteger('id_cuota')->nullable()->after('total_deuda');
            });
        }

        // 6. pagos.anulado
        if (Schema::hasTable('pagos') && ! Schema::hasColumn('pagos', 'anulado')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->string('anulado', 1)->nullable()->default('0')->after('total_pago');
            });
        }

        // 7. servicios.codigo
        if (Schema::hasTable('servicios') && ! Schema::hasColumn('servicios', 'codigo')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->string('codigo', 20)->nullable()->after('nombre');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('servicios') && Schema::hasColumn('servicios', 'codigo')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->dropColumn('codigo');
            });
        }

        if (Schema::hasTable('pagos') && Schema::hasColumn('pagos', 'anulado')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropColumn('anulado');
            });
        }

        if (Schema::hasTable('deudas') && Schema::hasColumn('deudas', 'id_cuota')) {
            Schema::table('deudas', function (Blueprint $table) {
                $table->dropColumn('id_cuota');
            });
        }

        if (Schema::hasTable('detalle_pagos')) {
            Schema::table('detalle_pagos', function (Blueprint $table) {
                if (Schema::hasColumn('detalle_pagos', 'id_deuda_cuota')) {
                    $table->dropColumn('id_deuda_cuota');
                }
                if (Schema::hasColumn('detalle_pagos', 'id_servicio')) {
                    $table->dropColumn('id_servicio');
                }
            });
        }

        if (Schema::hasTable('cuotas') && Schema::hasColumn('cuotas', 'id_anio')) {
            Schema::table('cuotas', function (Blueprint $table) {
                $table->dropColumn('id_anio');
            });
        }

        if (Schema::hasTable('socios') && Schema::hasColumn('socios', 'estado')) {
            Schema::table('socios', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }

        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'id_rol')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('id_rol');
            });
        }
    }
};

