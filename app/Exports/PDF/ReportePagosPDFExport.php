<?php

namespace App\Exports\PDF;

use App\Models\Pago;
use App\Models\Puesto;
use App\Models\Socio;
use App\Support\Comprobante;
use App\Support\FiltroTexto;
use Barryvdh\DomPDF\PDF;

class ReportePagosPDFExport
{
    public function generatePDF($filtro_id)
    {

        $nombre_socio = '';
        $nombre_bloque = '-';
        $numero_puesto = '-';
        $area = '-';
        $giro_negocio = '-';
        $query = Pago::query();

        if (request()->has('id_puesto') && request()->id_puesto != '') {
            $puesto = Puesto::with(['socio.persona', 'block', 'gironegocio'])->find($filtro_id);
            $numero_puesto = $puesto->numero_puesto ?? '-';
            $area = $puesto->area ?? '-';
            $nombre_bloque = $puesto->block ? $puesto->block->nombre : '-';
            $giro_negocio = $puesto->gironegocio ? $puesto->gironegocio->nombre : '-';
            $nombre_socio = $puesto->socio && $puesto->socio->persona
                ? $puesto->socio->persona->nombre_completo
                : '-';
            $query->whereHas('DetallePagos', function ($q) use ($filtro_id) {
                $q->where('id_puesto', $filtro_id);
            });
        } else {
            $socio = Socio::with('persona')->find($filtro_id);
            $nombre_socio = $socio && $socio->persona
                ? trim(($socio->persona->nombre ?? '').' '.($socio->persona->apellido_paterno ?? ''))
                : 'Socio';
            $query->where('id_socio', $filtro_id);
        }

        if (request()->has('nombre_socio') && request()->nombre_socio != '') {
            $texto = FiltroTexto::normalizarNombre(request()->nombre_socio);
            $query->whereHas('socio.persona', function ($q) use ($texto) {
                $q->whereRaw('upper(nombre_completo) LIKE upper(?)', ['%'.$texto.'%']);
            });
        }

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Setiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $pagos = $query->get()
            ->map(function ($pago) use ($meses) {
                $fecha = \Carbon\Carbon::parse($pago->fecha_registro);

                return [
                    'anio' => $fecha->year,
                    'mes' => $meses[$fecha->month],
                    'numero' => $pago->numero_pago,
                    'serie_numero' => Comprobante::formatear($pago->serie, $pago->numero_pago),
                    'fecha' => $fecha->format('Y-m-d'),
                    'aporte' => $pago->total_pago,
                    'total' => $pago->total_pago,
                    'detalle_pagos' => $pago->DetallePagos->map(function ($detalle) {
                        return ($detalle->servicio->nombre ?? 'Servicio').': '.$detalle->importe;
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

        $total_monto = $pagos->sum(function ($pago) {
            return $pago['detalles']->sum('importe');
        });

        // Total de la columna "Monto" tal como se muestra (total del pago en la última fila)
        $total_monto_detalle = $pagos->sum(function ($pago) {
            $detalles = $pago['detalles'];
            $last = count($detalles) - 1;
            $suma = 0;
            foreach ($detalles as $i => $detalle) {
                $suma += $i === $last ? (float) $pago['total'] : (float) $detalle['importe'];
            }

            return $suma;
        });

        $detalleMode = request()->query('modo') === 'detalle';

        $pdf = app(PDF::class)->loadView('exports.reporte_pagos', [
            'nombre_socio' => $nombre_socio,
            'nombre_bloque' => $nombre_bloque,
            'numero_puesto' => $numero_puesto,
            'area' => $area,
            'giro_negocio' => $giro_negocio,
            'pagos' => $pagos,
            'total' => $total,
            'total_monto' => $total_monto,
            'total_monto_detalle' => $total_monto_detalle,
            'modo_detalle' => $detalleMode,
        ]);

        return $pdf->download('reporte_pagos.pdf');

    }
}
