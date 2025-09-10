<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuotaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_cuota' => $this->id_cuota,
            'importe' => $this->importe,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'fecha_registro' => $this->fecha_registro,
            'global' => $this->global,

            'puestos_asignados' => $this->deudas->count(),

            'servicios' => $this->servicios->map(function ($item) {
                return [
                    'nombre' => $item->servicio->nombre ?? '(sin nombre)',
                    'costo_unitario' => $item->servicio->costo_unitario ?? 0,
                ];
            }),
        ];
    }
}
