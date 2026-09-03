<?php

namespace App\Http\Controllers;

use App\Exports\PDF\ReporteCuotasMetradoPDFExport;
use App\Exports\PDF\ReporteCuotasPuestoPDFExport;
use App\Exports\PDF\ReporteDeudasPDFExport;
use App\Exports\PDF\ReportePagosPDFExport;
use App\Exports\PDF\ReporteResumenPuestoPDFExport;
use App\Exports\ReporteCuotasMetradoExport;
use App\Exports\ReporteCuotasPuestoExport;
use App\Exports\ReporteDeudasExport;
use App\Exports\ReportePagosExport;
use App\Exports\ReporteResumenExport;
use App\Http\Resources\ReporteCuotaPorMetroCollection;
use App\Http\Resources\ReporteCuotaPorPuestoCollection;
use App\Http\Resources\ReporteDeudaCollection;
use App\Http\Resources\ReportePagoCollection;
use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\Pago;
use App\Support\Comprobante;
use App\Support\FiltroTexto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function pagos(Request $request)
    {
        $per_page = $request->get('per_page', 15);

        $query = Pago::query();

        if ($request->has('id_puesto') && $request->id_puesto != '') {
            $query->whereHas('DetallePagos', function ($q) use ($request) {
                $q->where('id_puesto', $request->id_puesto);
            });
        } elseif ($request->has('id_socio') && $request->id_socio != '') {
            $query->where('id_socio', $request->id_socio);
        }

        if ($request->has('nombre_socio') && $request->nombre_socio != '') {
            $texto = FiltroTexto::normalizarNombre($request->nombre_socio);
            $query->whereHas('socio.persona', function ($q) use ($texto) {
                $q->whereRaw('upper(nombre_completo) LIKE upper(?)', ['%'.$texto.'%']);
            });
        }

        $total_general = $query->sum('total_pago');
        $paginate = $query->paginate($per_page);

        return (new ReportePagoCollection($paginate))->additional([
            'total_general' => $total_general,
        ]);
    }

    public function exportReportePagos(Request $request)
    {

        $filtro = $request->id_puesto ?? $request->id_socio;

        return Excel::download(new ReportePagosExport($filtro), 'reporte_pagos.xlsx');
    }

    public function exportReportePagosPDF(Request $request)
    {
        $export = new ReportePagosPDFExport;
        $filtro = $request->id_puesto ?? $request->id_socio;

        return $export->generatePDF($filtro);
    }

    public function deudas(Request $request)
    {
        $per_page = $request->get('per_page', 15);

        $paginate = Deuda::where('id_puesto', $request->id_puesto)
            ->paginate($per_page);

        return new ReporteDeudaCollection($paginate);
    }

    public function exportReporteDeudas(Request $request)
    {
        return Excel::download(new ReporteDeudasExport($request->id_puesto, $request->nombre_socio), 'reporte_deudas.xlsx');
    }

    public function exportReporteDeudasPDF(Request $request)
    {
        $export = new ReporteDeudasPDFExport;

        return $export->generatePDF($request->id_puesto, $request->nombre_socio);
    }

    public function cuotaPorMetros(Request $request)
    {
        $per_page = $request->get('per_page', 15);
        $id_cuota = $request->get('id_cuota', 0);

        $paginate = Deuda::whereIn('id_deuda', function ($query) use ($id_cuota) {
            $query->select('a.id_deuda')
                ->from('deuda_cuotas as a')
                ->join('cuota_servicios as b', 'a.id_cuota_servicio', 'b.id_cuota_servicio')
                ->where('b.id_cuota', $id_cuota);
        })
            ->paginate($per_page);

        return new ReporteCuotaPorMetroCollection($paginate);
    }

    public function exportReporteCuotasMetrado(Request $request)
    {
        return Excel::download(new ReporteCuotasMetradoExport($request->id_cuota), 'reporte_cuotas_metrado.xlsx');
    }

    public function exportReporteCuotasMetradoPDF(Request $request)
    {
        $export = new ReporteCuotasMetradoPDFExport;

        return $export->generatePDF($request->id_cuota);
    }

    public function cuotaPorPuestos(Request $request)
    {
        $per_page = $request->get('per_page', 15);

        $paginate = Deuda::where('id_puesto', $request->id_puesto)
            ->paginate($per_page);

        return new ReporteCuotaPorPuestoCollection($paginate);
    }

    public function exportReporteCuotasPuesto(Request $request)
    {
        return Excel::download(new ReporteCuotasPuestoExport($request->id_puesto), 'reporte_cuotas_puesto.xlsx');
    }

    public function exportReporteCuotasPuestoPDF(Request $request)
    {
        $export = new ReporteCuotasPuestoPDFExport;

        return $export->generatePDF($request->id_puesto);
    }

    public function resumenPorPuestos(Request $request)
    {
        $per_page = $request->get('per_page', 15);

        $paginate = DetallePagos::select(
            'b.serie',
            'b.numero_pago',
            DB::raw("concat(b.serie, '-', b.numero_pago) as serie_numero"),
            DB::raw('b.total_pago as importe_ingreso'),
            DB::raw('sum(case when c.tipo_servicio = 1 then detalle_pagos.importe else 0 end) as importe_gastos_administrativo'),
            DB::raw('0 as importe_multas_inasistencia'),
            DB::raw('0 as importe_pagos_transferencia'),
            DB::raw('sum(case when c.tipo_servicio = 2 then detalle_pagos.importe else 0 end) as importe_cuotas_extraordinarias'),
            DB::raw('b.total_pago as importe_total')
        )
            ->join('pagos as b', 'detalle_pagos.id_pago', 'b.id_pago')
            ->join('servicios as c', 'detalle_pagos.id_servicio', 'c.id_servicio')
            ->where('detalle_pagos.id_puesto', $request->id_puesto)
            ->groupBy('b.total_pago', 'b.serie', 'b.numero_pago', 'b.id_pago'); // Agregado id_pago para estabilidad

        $paginado = $paginate->paginate($per_page);

        // Unifica el comprobante al formato estándar (0000-xxxxxxxx)
        $paginado->getCollection()->transform(function ($item) {
            $item->serie_numero = Comprobante::formatear($item->serie, $item->numero_pago);

            return $item;
        });

        return $paginado;
    }

    public function exportReporteResumenPorPuesto(Request $request)
    {
        return Excel::download(new ReporteResumenExport($request->id_puesto), 'reporte_resumen_puesto.xlsx');
    }

    public function exportReporteResumenPorPuestoPDF(Request $request)
    {
        $export = new ReporteResumenPuestoPDFExport;

        return $export->generatePDF($request->id_puesto);
    }

    public function dashboard(Request $request)
    {
        $year = now()->year;
        $month = now()->month;

        $cantidadSociosActivos = DB::table('socios')
            ->where('estado', '1')->count('*');

        $acumulacionPagos = DB::table('pagos')
            ->whereYear('fecha_registro', $year)
            ->whereMonth('fecha_registro', $month)
            ->sum('total_pago');

        $acumulacionDeudas = DB::table('deudas')
            ->whereYear('fecha_registro', $year)
            ->whereMonth('fecha_registro', $month)
            ->sum('total_deuda');

        $totalGeneral = $acumulacionPagos + $acumulacionDeudas;
        $porcentajePagos = $totalGeneral > 0 ? round(($acumulacionPagos / $totalGeneral) * 100, 2) : 0;
        $porcentajeDeudas = $totalGeneral > 0 ? round(($acumulacionDeudas / $totalGeneral) * 100, 2) : 0;

        $mesesEspanol = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $historico = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $m = (int) $date->month;
            $y = (int) $date->year;
            $nombreMes = $mesesEspanol[$m];

            $pagoMes = DB::table('pagos')
                ->whereYear('fecha_registro', $y)
                ->whereMonth('fecha_registro', $m)
                ->sum('total_pago');

            $deudaMes = DB::table('deudas')
                ->whereYear('fecha_registro', $y)
                ->whereMonth('fecha_registro', $m)
                ->sum('total_deuda');

            $historico[] = [
                'name' => $nombreMes,
                'pagos' => (float) $pagoMes,
                'deudas' => (float) $deudaMes,
            ];
        }

        $response = [
            'acumulacion_deuda' => number_format($acumulacionDeudas, 2, '.', ','),
            'acumulacion_pago' => number_format($acumulacionPagos, 2, '.', ','),
            'acumulacion_deuda_raw' => (float) $acumulacionDeudas,
            'acumulacion_pago_raw' => (float) $acumulacionPagos,
            'cantidad_socios_activos' => $cantidadSociosActivos,
            'porcentajes' => [
                'pagos' => $porcentajePagos,
                'deudas' => $porcentajeDeudas,
            ],
            'historico' => $historico,
        ];

        return response()->json($response);
    }
}
