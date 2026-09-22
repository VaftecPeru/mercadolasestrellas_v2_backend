<?php

namespace App\Http\Resources;

use App\Support\Comprobante;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReporteResumenPorPuestoCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection->transform(function ($detallePagos) {

                return [
                    'serie_numero' => $detallePagos->serie_numero ?? ($detallePagos->pago ? Comprobante::formatear($detallePagos->pago->serie, $detallePagos->pago->numero_pago) : '-'),
                    'importe_ingreso' => $detallePagos->importe_ingreso ?? $detallePagos->importe,
                    'importe_gastos_administrativo' => $detallePagos->importe_gastos_administrativo ?? 0,
                    'importe_otros_servicios' => $detallePagos->importe_otros_servicios ?? 0,
                    'importe_multas_inasistencia' => $detallePagos->importe_multas_inasistencia ?? 0,
                    'importe_pagos_banco' => $detallePagos->importe_pagos_banco ?? 0,
                    'importe_pagos_efectivo' => $detallePagos->importe_pagos_efectivo ?? 0,
                    'importe_cuotas_extraordinarias' => $detallePagos->importe_cuotas_extraordinarias ?? 0,
                    'importe_total' => $detallePagos->importe_total ?? $detallePagos->importe,
                ];
            }),
            'links' => [
                'self' => url('/reportes/resumen-por-puestos'),
            ],
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
