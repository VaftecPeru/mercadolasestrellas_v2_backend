<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e): JsonResponse
    {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Ocurrió un error inesperado en el servidor.',
            ], 500);
        }

        return parent::render($request, $e);
    }
}
