<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\DeudaController;
use App\Http\Controllers\GiroNegocioController;
use App\Http\Controllers\InquilinoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PuestoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\SocioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

// Bridge for v1 prefixed calls from frontend
Route::group(['prefix' => 'v1'], function () {
    Route::get('/blocks', [BlockController::class, 'index']);
    Route::post('/blocks', [BlockController::class, 'store']);

    Route::get('/giro-negocios', [GiroNegocioController::class, 'index']);
    Route::post('/giro-negocios', [GiroNegocioController::class, 'store']);

    Route::get('/inquilinos', [InquilinoController::class, 'index']);
    Route::post('/inquilinos', [InquilinoController::class, 'store']);
    Route::put('/inquilinos/{id_inquilino}', [InquilinoController::class, 'update']);
    Route::delete('/inquilinos/{id_inquilino}', [InquilinoController::class, 'destroy']);

    Route::get('/setup/bancos', [SetupController::class, 'indexBanco']);
    Route::get('/setup/banco-cuentas', [SetupController::class, 'indexBancoCuenta']);
    Route::get('/setup/modulos-web', [SetupController::class, 'indexModuloWeb']);

    Route::get('/deudas', [DeudaController::class, 'index']);
    Route::get('/deudas/pendientes', [DeudaController::class, 'deudaPendientes']);
    Route::post('/deudas/multa-inasistencia', [DeudaController::class, 'registrarMultaInasistencia']);
    Route::post('/deudas/registrar-multa-inasistencia', [DeudaController::class, 'registrarMultaInasistencia']); 

    Route::get('/cuotas', [CuotaController::class, 'index']);
    Route::post('/cuotas', [CuotaController::class, 'store']);
    Route::post('/cuotas/por-puestos', [CuotaController::class, 'storePorPuesto']);
    Route::post('/cuotas/por-multiples-puestos', [CuotaController::class, 'storePorMultiplesPuestos']);
    Route::put('/cuotas/{id}', [CuotaController::class, 'update']);
    Route::delete('/cuotas/{id}', [CuotaController::class, 'destroy']);
    Route::get('/cuotas/exportar', [CuotaController::class, 'export']);
    Route::get('/cuotas/exportar-pdf', [CuotaController::class, 'exportPDF']);



    Route::get('/socios', [SocioController::class, 'index']);
    Route::get('/socios/seleccionar', [SocioController::class, 'seleccionarSocio']);
    Route::get('/socios/puestos', [SocioController::class, 'listarPuestos']);

    Route::get('/socios/ver-puestos', [SocioController::class, 'listarPuestos']);
    Route::post('/socios', [SocioController::class, 'store']);
    Route::put('/socios/{id_socio}', [SocioController::class, 'update']);
    Route::delete('/socios/{id_socio}', [SocioController::class, 'destroy']);
    Route::get('/socios/exportar', [SocioController::class, 'export']);
    Route::get('/socios/exportar-pdf', [SocioController::class, 'exportPDF']);

    Route::get('/pagos', [PagoController::class, 'index']);
    Route::post('/pagos', [PagoController::class, 'store']);
    Route::post('/pagos/por-bancos', [PagoController::class, 'storePagoPorBanco']);
    Route::put('/pagos/{pago}', [PagoController::class, 'update']);
    Route::delete('/pagos/{pago}', [PagoController::class, 'destroy']);
    Route::get('/pagos/exportar', [PagoController::class, 'export']);
    Route::get('/pagos/exportar-pdf', [PagoController::class, 'exportPDF']);
    Route::post('/importar-pagos-excel', [PagoController::class, 'import']);

    Route::get('/puestos', [PuestoController::class, 'index']);
    Route::get('/puestos/sin-socio', [PuestoController::class, 'puestosSinSocio']);
    Route::get('/puestos/sin-inquilino', [PuestoController::class, 'puestosSinInquilino']);
    Route::get('/puestos/seleccionar', [PuestoController::class, 'seleccionarPuesto']);
    Route::get('/puestos/total', [PuestoController::class, 'obtenerTotalPuestos']);
    Route::get('/puestos/area-total', [PuestoController::class, 'obtenerAreaTotal']);
    Route::post('/puestos', [PuestoController::class, 'store']);
    Route::post('/puestos/asignar', [PuestoController::class, 'asignar']);
    Route::put('/puestos/{id_puesto}', [PuestoController::class, 'update']);
    Route::delete('/puestos/{id_puesto}', [PuestoController::class, 'destroy']);
    Route::get('/puestos/exportar', [PuestoController::class, 'export']);
    Route::get('/puestos/exportar-pdf', [PuestoController::class, 'exportPDF']);
    Route::post('/puestos/transferir', [PuestoController::class, 'transferir']);

    Route::get('/servicios', [ServicioController::class, 'index']);
    Route::get('/servicios/multa-inasistencia', [ServicioController::class, 'consultarImporteMultaInasistencia']);
    Route::get('/servicios/consultar-importe-multa-inasistencia', [ServicioController::class, 'consultarImporteMultaInasistencia']); 
    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::put('/servicios/{id_servicio}', [ServicioController::class, 'update']);
    Route::delete('/servicios/{id_servicio}', [ServicioController::class, 'destroy']);
    Route::get('/servicios/exportar', [ServicioController::class, 'export']);
    Route::get('/servicios/exportar-pdf', [ServicioController::class, 'exportPDF']);

    Route::get('/reportes/pagos', [ReporteController::class, 'pagos']);
    Route::get('/reportes/pagos/exportar', [ReporteController::class, 'exportReportePagos']);
    Route::get('/reportes/pagos/exportar-pdf', [ReporteController::class, 'exportReportePagosPDF']);
    Route::get('/reportes/deudas', [ReporteController::class, 'deudas']);
    Route::get('/reportes/deudas/exportar', [ReporteController::class, 'exportReporteDeudas']);
    Route::get('/reportes/deudas/exportar-pdf', [ReporteController::class, 'exportReporteDeudasPDF']);
    Route::get('/reportes/cuotas/metrado', [ReporteController::class, 'cuotaPorMetros']);
    Route::get('/reportes/cuotas/metrado/exportar', [ReporteController::class, 'exportReporteCuotasMetrado']);
    Route::get('/reportes/cuotas/metrado/exportar-pdf', [ReporteController::class, 'exportReporteCuotasMetradoPDF']);
    Route::get('/reportes/cuotas/puesto', [ReporteController::class, 'cuotaPorPuestos']);
    Route::get('/reportes/cuotas/puesto/exportar', [ReporteController::class, 'exportReporteCuotasPuesto']);
    Route::get('/reportes/cuotas/puesto/exportar-pdf', [ReporteController::class, 'exportReporteCuotasPuestoPDF']);
    Route::get('/reportes/resumen/puesto', [ReporteController::class, 'resumenPorPuestos']);
    Route::get('/reportes/resumen/puesto/exportar', [ReporteController::class, 'exportReporteResumenPorPuesto']);
    Route::get('/reportes/resumen/puesto/exportar-pdf', [ReporteController::class, 'exportReporteResumenPorPuestoPDF']);
    Route::get('/reportes/dashboard', [ReporteController::class, 'dashboard']);

    // Alias frontend
    Route::get('/reportes/cuota-por-metros', [ReporteController::class, 'cuotaPorMetros']);
    Route::get('/reportes/cuota-por-metros/exportar', [ReporteController::class, 'exportReporteCuotasMetrado']);
    Route::get('/reportes/cuota-por-puestos', [ReporteController::class, 'cuotaPorPuestos']);
    Route::get('/reportes/cuota-por-puestos/exportar', [ReporteController::class, 'exportReporteCuotasPuesto']);
    Route::get('/reportes/resumen-por-puestos', [ReporteController::class, 'resumenPorPuestos']);
    Route::get('/reportes/resumen-por-puestos/exportar', [ReporteController::class, 'exportReporteResumenPorPuesto']);
    Route::get('/reporte-deudas/exportar', [ReporteController::class, 'exportReporteDeudas']);

    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/validaciones', [LoginController::class, 'validaciones']);
});