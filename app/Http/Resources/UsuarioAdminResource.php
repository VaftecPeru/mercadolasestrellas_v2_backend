<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $persona = $this->Persona;
        if (! $persona && $this->Socio) {
            $persona = $this->Socio->persona;
        }

        return [
            'id_usuario' => $this->id_usuario,
            'nombre_usuario' => $this->nombre_usuario,
            'id_rol' => $this->id_rol,
            'rol' => $this->Rol ? $this->Rol->nombre : null,
            'estado' => $this->estado,
            'bloqueado' => $this->bloqueado,
            'debe_cambiar_password' => $this->debe_cambiar_password,
            'ultimo_acceso' => $this->ultimo_acceso,
            'fecha_registro' => $this->fecha_registro,
            'estado_cuenta' => $this->estado_cuenta,
            'nombre_completo' => $persona ? $persona->nombre_completo : null,
            'dni' => $persona ? $persona->dni : null,
            'nombre' => $persona ? $persona->nombre : null,
            'apellido_paterno' => $persona ? $persona->apellido_paterno : null,
            'apellido_materno' => $persona ? $persona->apellido_materno : null,
            'sexo' => $persona ? $persona->sexo : null,
            'direccion' => $persona ? $persona->direccion : null,
            'telefono' => $persona ? $persona->telefono : null,
            'correo' => $persona ? $persona->correo : null,
        ];
    }
}
