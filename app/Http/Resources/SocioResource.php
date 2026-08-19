<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Resource obtiene datos mediante relaciones con Persona
        return [
            'id_socio' => $this->id_socio,
            'id_usuario' => $this->id_usuario,
            'estado' => $this->estado,
            'fecha_registro' => $this->fecha_registro,
            
            // Datos personales desde la relación Persona
            'nombre_completo' => $this->persona ? $this->persona->nombre_completo : null,
            'nombre' => $this->persona ? $this->persona->nombre : null,
            'apellido_paterno' => $this->persona ? $this->persona->apellido_paterno : null,
            'apellido_materno' => $this->persona ? $this->persona->apellido_materno : null,
            'dni' => $this->persona ? $this->persona->dni : null,
            'correo' => $this->persona ? $this->persona->correo : null,
            'telefono' => $this->persona ? $this->persona->telefono : null,
            'direccion' => $this->persona ? $this->persona->direccion : null,
            'sexo' => $this->persona ? $this->persona->sexo : null,
            'tipo_persona' => $this->persona ? $this->persona->tipo_persona : null,
            
            // Relaciones
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
            'deuda' => new DeudaResource($this->whenLoaded('deuda')),
        ];
    }
}
