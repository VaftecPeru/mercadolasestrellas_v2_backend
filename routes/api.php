<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\DeudaController;
use App\Http\Controllers\GiroNegocioController;
use App\Http\Controllers\InquilinoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PuestoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\SocioController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
 * Autorización por módulo (id_modulo en la tabla `modulo`):
 *   1  Panel de Control · 3 Socios · 4 Puestos · 5 Servicios · 6 Generar Cuota
 *   7  Pagos · 8 Reporte Pagos · 9 Reporte Deudas · 10-12 reportes de cuotas/resumen · 13 Usuarios y Roles
 */
Route::group(['prefix' => 'v1'], function () {

    // ---------------------------------------------------------------------
    // Público (sin autenticación)
    // ---------------------------------------------------------------------
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::post('/cambiar-password', [LoginController::class, 'cambiarPassword']);
    Route::get('/validaciones', [LoginController::class, 'validaciones']);

    // Búsqueda rápida de puesto (público)
    Route::get('/puestos/seleccionar', [PuestoController::class, 'seleccionarPuesto']);
    Route::get('/reportes/deudas', [ReporteController::class, 'deudas']);
    Route::get('/reporte-deudas/exportar', [ReporteController::class, 'exportReporteDeudas']);
    Route::get('/reporte-deudas/exportar-pdf', [ReporteController::class, 'exportReporteDeudasPDF']);

    // ---------------------------------------------------------------------
    // Autenticado (cualquier rol activo) — lookups compartidos y lecturas
    // con scoping para el Socio
    // ---------------------------------------------------------------------
    Route::middleware('auth.token')->group(function () {
        Route::get('/blocks', [BlockController::class, 'index']);
        Route::get('/giro-negocios', [GiroNegocioController::class, 'index']);
        Route::get('/inquilinos', [InquilinoController::class, 'index']);

        Route::get('/setup/bancos', [SetupController::class, 'indexBanco']);
        Route::get('/setup/banco-cuentas', [SetupController::class, 'indexBancoCuenta']);
        Route::get('/setup/modulos-web', [SetupController::class, 'indexModuloWeb']);

        Route::get('/socios/seleccionar', [SocioController::class, 'seleccionarSocio']);
        Route::get('/puestos', [PuestoController::class, 'index']);
        Route::get('/puestos/sin-socio', [PuestoController::class, 'puestosSinSocio']);
        Route::get('/puestos/sin-inquilino', [PuestoController::class, 'puestosSinInquilino']);

        Route::get('/servicios/multa-inasistencia', [ServicioController::class, 'consultarImporteMultaInasistencia']);
        Route::get('/servicios/consultar-importe-multa-inasistencia', [ServicioController::class, 'consultarImporteMultaInasistencia']);

        Route::get('/deudas', [DeudaController::class, 'index']);
        Route::get('/deudas/pendientes', [DeudaController::class, 'deudaPendientes']);

        Route::get('/reportes/pagos', [ReporteController::class, 'pagos']);
        Route::get('/reportes/pagos/exportar', [ReporteController::class, 'exportReportePagos']);
        Route::get('/reportes/pagos/exportar-pdf', [ReporteController::class, 'exportReportePagosPDF']);
        Route::get('/reportes/deudas/exportar', [ReporteController::class, 'exportReporteDeudas']);
        Route::get('/reportes/deudas/exportar-pdf', [ReporteController::class, 'exportReporteDeudasPDF']);

        // Dashboard (Panel de Control — módulo 1)
        Route::get('/reportes/dashboard', [ReporteController::class, 'dashboard'])->middleware('permiso:1');

        // Socios (módulo 3)
        Route::middleware('permiso:3')->group(function () {
            Route::get('/socios', [SocioController::class, 'index']);
            Route::get('/socios/puestos', [SocioController::class, 'listarPuestos']);
            Route::get('/socios/ver-puestos', [SocioController::class, 'listarPuestos']);
            Route::post('/socios', [SocioController::class, 'store']);
            Route::post('/socios/{id_socio}/toggle-acceso', [SocioController::class, 'toggleAcceso']);
            Route::post('/socios/{id_socio}/regenerar-credenciales', [SocioController::class, 'regenerarCredenciales']);
            Route::put('/socios/{id_socio}', [SocioController::class, 'update']);
            Route::delete('/socios/{id_socio}', [SocioController::class, 'destroy']);
            Route::get('/socios/exportar', [SocioController::class, 'export']);
            Route::get('/socios/exportar-pdf', [SocioController::class, 'exportPDF']);
        });

        // Puestos (módulo 4)
        Route::middleware('permiso:4')->group(function () {
            Route::post('/puestos', [PuestoController::class, 'store']);
            Route::post('/puestos/asignar', [PuestoController::class, 'asignar']);
            Route::post('/puestos/transferir', [PuestoController::class, 'transferir']);
            Route::put('/puestos/{id_puesto}', [PuestoController::class, 'update']);
            Route::delete('/puestos/{id_puesto}', [PuestoController::class, 'destroy']);
            Route::get('/puestos/total', [PuestoController::class, 'obtenerTotalPuestos']);
            Route::get('/puestos/area-total', [PuestoController::class, 'obtenerAreaTotal']);
            Route::get('/puestos/exportar', [PuestoController::class, 'export']);
            Route::get('/puestos/exportar-pdf', [PuestoController::class, 'exportPDF']);
            Route::post('/blocks', [BlockController::class, 'store']);
            Route::post('/giro-negocios', [GiroNegocioController::class, 'store']);
            Route::post('/inquilinos', [InquilinoController::class, 'store']);
            Route::put('/inquilinos/{id_inquilino}', [InquilinoController::class, 'update']);
            Route::delete('/inquilinos/{id_inquilino}', [InquilinoController::class, 'destroy']);
        });

        // Servicios (módulo 5)
        Route::middleware('permiso:5')->group(function () {
            Route::get('/servicios', [ServicioController::class, 'index']);
            Route::post('/servicios', [ServicioController::class, 'store']);
            Route::put('/servicios/{id_servicio}', [ServicioController::class, 'update']);
            Route::delete('/servicios/{id_servicio}', [ServicioController::class, 'destroy']);
            Route::get('/servicios/exportar', [ServicioController::class, 'export']);
            Route::get('/servicios/exportar-pdf', [ServicioController::class, 'exportPDF']);
        });

        // Generar Cuota (módulo 6) — listado también para Cajero (módulo 10, selector de cuotas)
        Route::get('/cuotas', [CuotaController::class, 'index'])->middleware('permiso:6,10');
        Route::middleware('permiso:6')->group(function () {
            Route::post('/cuotas', [CuotaController::class, 'store']);
            Route::post('/cuotas/por-puestos', [CuotaController::class, 'storePorPuesto']);
            Route::post('/cuotas/por-multiples-puestos', [CuotaController::class, 'storePorMultiplesPuestos']);
            Route::put('/cuotas/{id}', [CuotaController::class, 'update']);
            Route::delete('/cuotas/{id}', [CuotaController::class, 'destroy']);
            Route::get('/cuotas/exportar', [CuotaController::class, 'export']);
            Route::get('/cuotas/exportar-pdf', [CuotaController::class, 'exportPDF']);
        });

        // Pagos (módulo 7)
        Route::middleware('permiso:7')->group(function () {
            Route::get('/pagos', [PagoController::class, 'index']);
            Route::post('/pagos', [PagoController::class, 'store']);
            Route::post('/pagos/por-bancos', [PagoController::class, 'storePagoPorBanco']);
            Route::put('/pagos/{pago}', [PagoController::class, 'update']);
            Route::delete('/pagos/{pago}', [PagoController::class, 'destroy']);
            Route::get('/pagos/exportar', [PagoController::class, 'export']);
            Route::get('/pagos/exportar-pdf', [PagoController::class, 'exportPDF']);
            Route::post('/importar-pagos-excel', [PagoController::class, 'import']);
            Route::post('/deudas/multa-inasistencia', [DeudaController::class, 'registrarMultaInasistencia']);
            Route::post('/deudas/registrar-multa-inasistencia', [DeudaController::class, 'registrarMultaInasistencia']);
        });

        // Usuarios y Roles (módulo 13)
        Route::middleware('permiso:13')->group(function () {
            Route::get('/usuarios', [UsuarioController::class, 'index']);
            Route::get('/usuarios/estadisticas', [UsuarioController::class, 'estadisticas']);
            Route::get('/usuarios/socios-sin-cuenta', [UsuarioController::class, 'listarSociosSinCuenta']);
            Route::post('/usuarios/generar-cuentas-socios', [UsuarioController::class, 'generarCuentasSocios']);
            Route::post('/usuarios', [UsuarioController::class, 'store']);
            Route::put('/usuarios/{id_usuario}', [UsuarioController::class, 'update']);
            Route::post('/usuarios/{id_usuario}/activar', [UsuarioController::class, 'activar']);
            Route::post('/usuarios/{id_usuario}/desactivar', [UsuarioController::class, 'desactivar']);
            Route::post('/usuarios/{id_usuario}/bloquear', [UsuarioController::class, 'bloquear']);
            Route::post('/usuarios/{id_usuario}/desbloquear', [UsuarioController::class, 'desbloquear']);
            Route::post('/usuarios/{id_usuario}/generar-password-temporal', [UsuarioController::class, 'generarPasswordTemporal']);
            Route::put('/usuarios/{id_usuario}/telefono', [UsuarioController::class, 'actualizarTelefono']);
            Route::get('/roles', [UsuarioController::class, 'indexRol']);
            Route::get('/modulos', [UsuarioController::class, 'indexModulo']);
            Route::get('/roles/{id_rol}/modulos', [UsuarioController::class, 'modulosRol']);
            Route::put('/roles/{id_rol}/modulos', [UsuarioController::class, 'actualizarModulosRol']);
        });

        // Reporte de cuotas por metrado (módulo 10)
        Route::middleware('permiso:10')->group(function () {
            Route::get('/reportes/cuotas/metrado', [ReporteController::class, 'cuotaPorMetros']);
            Route::get('/reportes/cuotas/metrado/exportar', [ReporteController::class, 'exportReporteCuotasMetrado']);
            Route::get('/reportes/cuotas/metrado/exportar-pdf', [ReporteController::class, 'exportReporteCuotasMetradoPDF']);
            Route::get('/reportes/cuota-por-metros', [ReporteController::class, 'cuotaPorMetros']);
            Route::get('/reportes/cuota-por-metros/exportar', [ReporteController::class, 'exportReporteCuotasMetrado']);
            Route::get('/reportes/cuota-por-metros/exportar-pdf', [ReporteController::class, 'exportReporteCuotasMetradoPDF']);
        });

        // Reporte de cuotas por puestos (módulo 11)
        Route::middleware('permiso:11')->group(function () {
            Route::get('/reportes/cuotas/puesto', [ReporteController::class, 'cuotaPorPuestos']);
            Route::get('/reportes/cuotas/puesto/exportar', [ReporteController::class, 'exportReporteCuotasPuesto']);
            Route::get('/reportes/cuotas/puesto/exportar-pdf', [ReporteController::class, 'exportReporteCuotasPuestoPDF']);
            Route::get('/reportes/cuota-por-puestos', [ReporteController::class, 'cuotaPorPuestos']);
            Route::get('/reportes/cuota-por-puestos/exportar', [ReporteController::class, 'exportReporteCuotasPuesto']);
            Route::get('/reportes/cuota-por-puestos/exportar-pdf', [ReporteController::class, 'exportReporteCuotasPuestoPDF']);
        });

        // Reporte de resumen (módulo 12)
        Route::middleware('permiso:12')->group(function () {
            Route::get('/reportes/resumen/puesto', [ReporteController::class, 'resumenPorPuestos']);
            Route::get('/reportes/resumen/puesto/exportar', [ReporteController::class, 'exportReporteResumenPorPuesto']);
            Route::get('/reportes/resumen/puesto/exportar-pdf', [ReporteController::class, 'exportReporteResumenPorPuestoPDF']);
            Route::get('/reportes/resumen-por-puestos', [ReporteController::class, 'resumenPorPuestos']);
            Route::get('/reportes/resumen-por-puestos/exportar', [ReporteController::class, 'exportReporteResumenPorPuesto']);
            Route::get('/reportes/resumen-por-puestos/exportar-pdf', [ReporteController::class, 'exportReporteResumenPorPuestoPDF']);
        });
    });
});
