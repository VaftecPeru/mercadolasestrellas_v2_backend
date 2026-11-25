<?php

namespace App\Exports\PDF;

use App\Models\Pago;
use App\Models\Puesto;
use App\Models\Servicio;
use App\Models\Socio;
use App\Support\Comprobante;
use App\Support\FiltroTexto;
use Barryvdh\DomPDF\PDF;

class ReportePagosPDFExport
{
    public function generatePDF($id_puesto = null, $id_socio = null, $nombre_socio = null)
    {
        // Soporte retrocompatible si se llama con 1 solo parámetro
        if (func_num_args() === 1 && ! is_array($id_puesto)) {
            if (request()->has('id_puesto') && request()->id_puesto != '') {
                $idPuestoFinal = request()->id_puesto;
                $idSocioFinal = request()->input('id_socio');
            } else {
                $idSocioFinal = $id_puesto ?: request()->input('id_socio');
                $idPuestoFinal = null;
            }
            $nombreSocioFinal = request()->input('nombre_socio');
        } else {
            $idPuestoFinal = $id_puesto ?: request()->input('id_puesto');
            $idSocioFinal = $id_socio ?: request()->input('id_socio');
            $nombreSocioFinal = $nombre_socio ?: request()->input('nombre_socio');
        }

        $nombre_socio = '-';
        $nombre_bloque = '-';
        $numero_puesto = '-';
        $area = '-';
        $giro_negocio = '-';
        $query = Pago::query();

        if ($idPuestoFinal) {
            $puesto = Puesto::with(['socio.persona', 'block', 'gironegocio'])->find($idPuestoFinal);
            if ($puesto) {
                $numero_puesto = $puesto->numero_puesto ?? '-';
                $area = $puesto->area ?? '-';
                $nombre_bloque = $puesto->block ? $puesto->block->nombre : '-';
                $giro_negocio = $puesto->gironegocio ? $puesto->gironegocio->nombre : '-';
                $nombre_socio = $puesto->socio && $puesto->socio->persona
                    ? $puesto->socio->persona->nombre_completo
                    : '-';
            }
            $query->whereHas('DetallePagos', function ($q) use ($idPuestoFinal) {
                $q->where('id_puesto', $idPuestoFinal);
            });
        }

        if ($nombre_socio === '-' && $idSocioFinal) {
            $socio = Socio::with('persona')->find($idSocioFinal);
            if ($socio && $socio->persona) {
                $nombre_socio = $socio->persona->nombre_completo ?: 'Socio';
            }
        }

        if ($idSocioFinal) {
            $query->where('id_socio', $idSocioFinal);
        } elseif (! empty($nombreSocioFinal)) {
            $texto = FiltroTexto::normalizarNombre($nombreSocioFinal);
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
                            'tipo_servicio' => isset($detalle->servicio) ? Servicio::nombreTipoServicio($detalle->servicio->tipo_servicio) : '-',
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
