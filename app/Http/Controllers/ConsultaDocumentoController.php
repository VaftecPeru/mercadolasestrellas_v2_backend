<?php

namespace App\Http\Controllers;

use App\Services\ConsultaDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultaDocumentoController extends Controller
{
    public function __construct(private ConsultaDocumentoService $consultaService) {}

    /**
     * Consulta información de una persona por su número de DNI.
     */
    public function consultarDni(Request $request, string $numero): JsonResponse
    {
        $forzar = $request->boolean('forzar', false);
        $resultado = $this->consultaService->consultarDni($numero, $forzar);

        if (! $resultado['success']) {
            $status = match ($resultado['codigo'] ?? '') {
                'DNI_INVALIDO' => 422,
                'NO_ENCONTRADO' => 404,
                'ERROR_CONEXION', 'ERROR_SERVICIO', 'TOKEN_NO_CONFIGURADO' => 503,
                default => 400,
            };

            return response()->json($resultado, $status);
        }

        return response()->json($resultado, 200);
    }

    /**
     * Consulta información de un contribuyente o empresa por su número de RUC.
     */
    public function consultarRuc(Request $request, string $numero): JsonResponse
    {
        $resultado = $this->consultaService->consultarRuc($numero);

        if (! $resultado['success']) {
            $status = match ($resultado['codigo'] ?? '') {
                'RUC_INVALIDO' => 422,
                'NO_ENCONTRADO' => 404,
                'ERROR_CONEXION', 'ERROR_SERVICIO', 'TOKEN_NO_CONFIGURADO' => 503,
                default => 400,
            };

            return response()->json($resultado, $status);
        }

        return response()->json($resultado, 200);
    }
}
