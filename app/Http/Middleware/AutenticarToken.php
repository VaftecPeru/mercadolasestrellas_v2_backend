<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutenticarToken
{
    /**
     * Autentica la petición mediante el token de sesión y deja al usuario
     * disponible en $request->attributes('usuario').
     *
     * Rechaza (401/403) token ausente/inválido, cuenta bloqueada/desactivada
     * o con cambio de contraseña pendiente.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->input('token');

        if (! $token) {
            return response()->json(['message' => 'No autenticado. Token requerido.'], 401);
        }

        $usuario = Usuario::where('token', $token)->first();

        if (! $usuario) {
            return response()->json(['message' => 'Token inválido o expirado.'], 401);
        }

        if ($usuario->bloqueado) {
            return response()->json(['message' => 'Su cuenta está bloqueada.'], 403);
        }

        if ($usuario->estado !== '1') {
            return response()->json(['message' => 'Su cuenta está desactivada.'], 403);
        }

        if ($usuario->debe_cambiar_password) {
            return response()->json([
                'message' => 'Debe cambiar su contraseña antes de continuar.',
                'debe_cambiar_password' => true,
            ], 403);
        }

        $request->attributes->set('usuario', $usuario);

        return $next($request);
    }
}
