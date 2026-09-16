<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_usuario' => $this->id_usuario,
            'nombre_usuario' => $this->nombre_usuario,
            'id_rol' => $this->id_rol,
            'estado' => $this->estado,
            'debe_cambiar_password' => $this->debe_cambiar_password,
            'bloqueado' => $this->bloqueado,
            'fecha_registro' => $this->fecha_registro,
        ];
    }
}
