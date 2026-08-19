<?php

namespace App\Exports\PDF;

use App\Models\Pago;
use App\Models\Socio;
use App\Models\Puesto;
use Barryvdh\DomPDF\PDF;

class ReportePagosPDFExport {

    public function generatePDF($filtro_id) {

        $nombre_reporte = "";
        $query = Pago::query();

        if (request()->has('id_puesto') && request()->id_puesto != "") {
            $puesto = Puesto::find($filtro_id);
            $nombre_reporte = "Puesto: " . ($puesto->numero_puesto ?? $filtro_id);
            $query->whereHas('DetallePagos', function($q) use ($filtro_id) {
                $q->where('id_puesto', $filtro_id);
            });
        } else {
            $socio = Socio::with('persona')->find($filtro_id);
            $nombre_reporte = "Socio: " . ($socio->persona->nombre ?? "Socio") . " " . ($socio->persona->apellido_paterno ?? "");
            $query->where('id_socio', $filtro_id);
        }

        $meses = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
        ];

        $pagos = $query->get()
            ->map(function ($pago) use ($meses) {
                $fecha = \Carbon\Carbon::parse($pago->fecha_registro);
                return [
                    'anio' => $fecha->year,
                    'mes' => $meses[$fecha->month],
                    'numero' => $pago->numero_pago,
                    'serie_numero' => $pago->serie.'-'.$pago->numero_pago,
                    'fecha' => $fecha->format('Y-m-d'),
                    'aporte' => $pago->total_pago,
                    'total' => $pago->total_pago,
                    'detalle_pagos' => $pago->DetallePagos->map(function ($detalle) {
                            return ($detalle->servicio->nombre ?? 'Servicio') . ': ' . $detalle->importe;
                        })->join('\n'),
                    'detalles' => $pago->DetallePagos->map(function ($detalle) {
                            return [
                                'servicio_nombre' => $detalle->servicio->nombre ?? 'Servicio',
                                'importe' => $detalle->importe,
                            ];
                        }),
                ];
            });

        $total = $query->sum('total_pago');

        $pdf = app(PDF::class)->loadView('exports.reporte_pagos', [
            'nombre_socio' => $nombre_reporte,
            'pagos' => $pagos, 
            'total' => $total, 
        ]);

        return $pdf->download('reporte_pagos.pdf');

    }

}