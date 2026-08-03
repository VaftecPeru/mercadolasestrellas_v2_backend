<?php

use App\Http\Controllers\PagoController;
use App\Http\Controllers\SocioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/csrf-token', function () {
    return response()->json([
        'token' => csrf_token()
    ]);
});

Route::get('/socios', [SocioController::class, 'index']);
Route::get('/socios/seleccionar', [\App\Http\Controllers\SocioController::class, 'seleccionarSocio']);
Route::get('/socios/export', [SocioController::class, 'export']);
Route::get('/socios/exportar', [SocioController::class, 'export']);
Route::get('/socios/export-pdf', [SocioController::class, 'exportPDF']);

Route::get('/pagos', [PagoController::class, 'index']);
Route::get('/pagos/export', [PagoController::class, 'export']);
Route::get('/pagos/export-pdf', [PagoController::class, 'exportPDF']);
