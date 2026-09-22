<?php

namespace App\Exports\PDF;

use App\Models\Puesto;
use App\Support\Comprobante;
use App\Support\ReporteResumen;
use App\Util\Util;
use Barryvdh\DomPDF\PDF;

class ReporteResumenPuestoPDFExport
{
    public function generatePDF($id_puesto)
    {

        $puesto = Puesto::find($id_puesto);
        $nombre_socio = $puesto->socio->persona->nombre_completo;
        $nombre_bloque = $puesto->block ? $puesto->block->nombre : '-';
        $numero_puesto = $puesto->numero_puesto;
        $area = $puesto->area;
        $giro_negocio = $puesto->gironegocio ? $puesto->gironegocio->nombre : '-';

        $pagos = ReporteResumen::query($id_puesto)
            ->get()
            ->map(function ($row) {
                return [
                    'numero_pago' => Comprobante::formatear($row->serie ?? '', $row->numero_pago ?? ''),
                    'importe_ingreso' => $row->importe_ingreso,
                    'importe_gastos_administrativo' => $row->importe_gastos_administrativo,
                    'importe_otros_servicios' => $row->importe_otros_servicios,
                    'importe_multas_inasistencia' => $row->importe_multas_inasistencia,
                    'importe_pagos_banco' => $row->importe_pagos_banco,
                    'importe_pagos_efectivo' => $row->importe_pagos_efectivo,
                    'importe_cuotas_extraordinarias' => $row->importe_cuotas_extraordinarias,
                ];
            });

        $pagosArray = json_decode(json_encode($pagos), true);
        $total_importe_ingreso = Util::sumaColArrayObjFormat($pagosArray, 'importe_ingreso');
        $total_importe_gastos_administrativo = Util::sumaColArrayObjFormat($pagosArray, 'importe_gastos_administrativo');
        $total_importe_otros_servicios = Util::sumaColArrayObjFormat($pagosArray, 'importe_otros_servicios');
        $total_importe_multas_inasistencia = Util::sumaColArrayObjFormat($pagosArray, 'importe_multas_inasistencia');
        $total_importe_pagos_banco = Util::sumaColArrayObjFormat($pagosArray, 'importe_pagos_banco');
        $total_importe_pagos_efectivo = Util::sumaColArrayObjFormat($pagosArray, 'importe_pagos_efectivo');
        $total_importe_cuotas_extraordinarias = Util::sumaColArrayObjFormat($pagosArray, 'importe_cuotas_extraordinarias');

        $pdf = app(PDF::class)->loadView('exports.reporte_resumen_puesto', [
            'nombre_socio' => $nombre_socio,
            'nombre_bloque' => $nombre_bloque,
            'numero_puesto' => $numero_puesto,
            'area' => $area,
            'giro_negocio' => $giro_negocio,
            'pagos' => $pagos,
            'total_importe_ingreso' => $total_importe_ingreso,
            'total_importe_gastos_administrativo' => $total_importe_gastos_administrativo,
            'total_importe_otros_servicios' => $total_importe_otros_servicios,
            'total_importe_multas_inasistencia' => $total_importe_multas_inasistencia,
            'total_importe_pagos_banco' => $total_importe_pagos_banco,
            'total_importe_pagos_efectivo' => $total_importe_pagos_efectivo,
            'total_importe_cuotas_extraordinarias' => $total_importe_cuotas_extraordinarias,
        ]);

        return $pdf->download('reporte_resumen_puesto.pdf');

    }
}
