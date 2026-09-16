<?php

namespace App\Support;

use App\Models\Puesto;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Illuminate\Http\Request;

trait ScopeSocio
{
    /**
     * Usuario autenticado (asignado por el middleware AutenticarToken).
     */
    protected function usuarioAutenticado(Request $request): ?Usuario
    {
        return $request->attributes->get('usuario');
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

        if (! $usuario || ! $usuario->Socio) {
            return null;
        }

        return (int) $usuario->Socio->id_socio;
    }

    /**
     * Si el usuario es Socio, fuerza id_socio a su propio id y elimina los filtros
     * que permitirían consultar información de otros socios (id_puesto, nombre_socio).
     */
    protected function aplicarScopeSocio(Request $request): void
    {
        if (! $this->esSocio($request)) {
            return;
        }

        $idSocio = $this->idSocioAutenticado($request);
        if ($idSocio === null) {
            return;
        }

        $request->merge(['id_socio' => $idSocio]);
        $request->query->set('id_socio', $idSocio);
        $request->query->remove('id_puesto');
        $request->query->remove('nombre_socio');
        $request->request->remove('id_puesto');
        $request->request->remove('nombre_socio');
    }

    /**
     * Verifica que el puesto solicitado pertenezca al Socio autenticado.
     * Devuelve false si el Socio intenta acceder a un puesto que no es suyo.
     */
    protected function verificarPuestoDelSocio(Request $request): bool
    {
        if (! $this->esSocio($request)) {
            return true;
        }

        $idSocio = $this->idSocioAutenticado($request);
        $idPuesto = $request->input('id_puesto');

        if (! $idPuesto || $idSocio === null) {
            return false;
        }

        return Puesto::where('id_puesto', $idPuesto)->where('id_socio', $idSocio)->exists();
    }
}
