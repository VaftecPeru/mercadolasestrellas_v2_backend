<?php

namespace App\Http\Middleware;

use App\Services\UsuarioService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    /**
     * Autoriza por rol_modulo: el usuario debe tener permiso para al menos
     * uno de los módulos indicados. El Administrador (id_rol=1) tiene acceso total.
     */
    public function handle(Request $request, Closure $next, ...$modulos): Response
    {
        $usuario = $request->attributes->get('usuario');

        if (! $usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if ((int) $usuario->id_rol === UsuarioService::ID_ROL_ADMINISTRADOR) {
            return $next($request);
        }

        $idRol = (int) $usuario->id_rol;

        foreach ($modulos as $modulo) {
            $permitido = DB::table('rol_modulo')
                ->where('id_rol', $idRol)
                ->where('id_modulo', (int) $modulo)
                ->exists();

            if ($permitido) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'No tiene permiso para realizar esta acción.'], 403);
    }
}
