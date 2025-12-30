<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportePagoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        try {
            // Obtenemos el ID del puesto desde el request para filtrar los detalles
            $idPuestoRequest = $request->get('id_puesto');

            // Consulta directa a la base de datos para obtener los detalles
            $queryDetalles = DB::table('detalle_pagos')
                ->leftJoin('servicios', 'detalle_pagos.id_servicio', '=', 'servicios.id_servicio')
                ->select(
                    'detalle_pagos.importe',
                    'servicios.nombre as descripcion'
                )
                ->where('detalle_pagos.id_pago', $this->id_pago);

            // Filtramos por puesto si se solicita un reporte de puesto específico
            if ($idPuestoRequest && $idPuestoRequest != "") {
                $queryDetalles->where('detalle_pagos.id_puesto', $idPuestoRequest);
            }

            $detalles = $queryDetalles->get();

            return [
                'id_pago' => $this->id_pago,
                'fecha' => $this->fecha_registro,
                'total' => number_format($this->total_pago, 2, '.', ''),
                'detalle_pagos' => $detalles->map(function ($d) {
                    return [
                        'importe' => number_format($d->importe, 2, '.', ''),
                        'descripcion' => $d->descripcion ?? 'Servicio/Aporte'
                    ];
                }),
            ];

        } catch (\Exception $e) {
            return [
                'id_pago' => $this->id_pago ?? '?',
                'fecha' => $this->fecha_registro ?? '',
                'total' => $this->total_pago ?? 0,
                'detalle_pagos' => [],
            ];
        }
    }
}
