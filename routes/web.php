<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/csrf-token', function () {
    return response()->json([
        'token' => csrf_token()
    ]);
});

Route::get('/socios/seleccionar', [\App\Http\Controllers\SocioController::class, 'seleccionarSocio']);
Route::get('/pagos', [\App\Http\Controllers\PagoController::class, 'index']);
