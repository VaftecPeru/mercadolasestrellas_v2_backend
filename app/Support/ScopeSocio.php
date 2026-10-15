<?php

namespace App\Support;

use App\Models\Puesto;
use App\Models\Socio;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Illuminate\Http\Request;

trait ScopeSocio
{
    /**
     * Usuario autenticado (asignado por el middleware AutenticarToken o resuelto por token).
     */
    protected function usuarioAutenticado(Request $request): ?Usuario
    {
        $usuario = $request->attributes->get('usuario');
        if (! $usuario && $request->user() instanceof Usuario) {
            $usuario = $request->user();
            $request->attributes->set('usuario', $usuario);
        }
        if (! $usuario) {
            $token = $request->bearerToken() ?? $request->input('token');
            if ($token) {
                $usuario = Usuario::where('token', $token)->first();
                if ($usuario) {
                    $request->attributes->set('usuario', $usuario);
                }
            }
        }

        return $usuario;
    }

    /**
     * Indica si el usuario autenticado tiene rol Socio.
     */
    protected function esSocio(Request $request): bool
    {
        $usuario = $this->usuarioAutenticado($request);

        return $usuario && (int) $usuario->id_rol === UsuarioService::ID_ROL_SOCIO;
    }

    /**
     * id_socio del usuario autenticado (null si no es Socio o no tiene socio vinculado).
     */
    protected function idSocioAutenticado(Request $request): ?int
    {
        $usuario = $this->usuarioAutenticado($request);
        if (! $usuario) {
            return null;
        }
        if ($usuario->Socio) {
            return (int) $usuario->Socio->id_socio;
        }
        $socioPorUsuario = Socio::where('id_usuario', $usuario->id_usuario)->first();
        if ($socioPorUsuario) {
            return (int) $socioPorUsuario->id_socio;
        }
        if ($usuario->id_persona) {
            $socioPorPersona = Socio::where('id_socio', $usuario->id_persona)->first();
            if ($socioPorPersona) {
                return (int) $socioPorPersona->id_socio;
            }
        }

        return null;
    }

    /**
     * Si el usuario es Socio, fuerza autoritativamente id_socio a su propio id,
     * elimina nombre_socio (evita búsquedas por nombre que puedan eludir el scope)
     * y valida que cualquier id_puesto solicitado le pertenezca estrictamente.
     */
    protected function aplicarScopeSocio(Request $request): void
    {
        if (! $this->esSocio($request)) {
            return;
        }

        $idSocio = $this->idSocioAutenticado($request);
        if ($idSocio === null) {
            abort(response()->json(['message' => 'No tiene un socio vinculado a su cuenta de usuario.'], 403));
        }

        // Forzar id_socio autoritativo del socio autenticado
        $request->merge(['id_socio' => $idSocio]);
        $request->query->set('id_socio', $idSocio);

        // Prevenir manipulación por nombre de socio
        $request->query->remove('nombre_socio');
        $request->request->remove('nombre_socio');

        $idPuesto = $request->input('id_puesto');
        if ($idPuesto) {
            $puestoValido = Puesto::where('id_puesto', $idPuesto)
                ->where('id_socio', $idSocio)
                ->exists();

            if (! $puestoValido) {
                abort(response()->json(['message' => 'No tiene autorización para consultar o exportar un puesto que no le pertenece.'], 403));
            }

            $request->merge(['id_puesto' => (int) $idPuesto]);
            $request->query->set('id_puesto', (int) $idPuesto);
        } else {
            $request->query->remove('id_puesto');
            $request->request->remove('id_puesto');
        }
    }

    /**
     * Verifica que el puesto solicitado pertenezca al Socio autenticado.
     * Si no se especifica puesto, devuelve true (consulta global del socio).
     * Si el usuario no es Socio, devuelve true.
     */
    protected function verificarPuestoDelSocio(Request $request): bool
    {
        if (! $this->esSocio($request)) {
            return true;
        }

        $idSocio = $this->idSocioAutenticado($request);
        if ($idSocio === null) {
            return false;
        }

        $idPuesto = $request->input('id_puesto');
        if (! $idPuesto) {
            return true;
        }

        return Puesto::where('id_puesto', $idPuesto)->where('id_socio', $idSocio)->exists();
    }
}
