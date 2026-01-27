<?php

use App\Http\Controllers\PagoController;
use App\Http\Controllers\SocioController;
use Illuminate\Support\Facades\Route;

// Bridge for v1 prefixed calls from frontend
Route::group(['prefix' => 'v1'], function () {
    Route::get('/socios/seleccionar', [SocioController::class, 'seleccionarSocio']);
    Route::get('/pagos', [PagoController::class, 'index']);
    Route::post('/pagos', [PagoController::class, 'store']);
    Route::post('/pagos/por-bancos', [PagoController::class, 'storePagoPorBanco']);
    Route::put('/pagos/{pago}', [PagoController::class, 'update']);
    Route::delete('/pagos/{pago}', [PagoController::class, 'destroy']);
    Route::post('/importar-pagos-excel', [PagoController::class, 'import']);
});