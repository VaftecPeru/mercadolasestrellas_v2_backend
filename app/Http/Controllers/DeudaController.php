<?php

namespace App\Http\Controllers;

use App\Filters\DeudaFilter;
use App\Http\Resources\DeudaCollection;
use App\Models\Cuota;
use App\Models\CuotaServicios;
use App\Models\Deuda;
use App\Models\DeudaCuota;
use App\Models\PuestoCuota;
use App\Models\Servicio;
use App\Support\FiltroTexto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeudaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = new DeudaFilter;
        $queryItems = $filter->transform($request);
        $deudas = Deuda::where($queryItems)->paginate();

        return new DeudaCollection($deudas->appends($request->query()));
    }

    public function deudaPendientes(Request $request)
    {
        $per_page = $request->get('per_page', 15);

        $query = DeudaCuota::join('deudas', 'deuda_cuotas.id_deuda', 'deudas.id_deuda')
            ->join('cuota_servicios', 'deuda_cuotas.id_cuota_servicio', 'cuota_servicios.id_cuota_servicio')
            ->join('servicios', 'cuota_servicios.id_servicio', 'servicios.id_servicio')
            ->leftJoin('detalle_pagos', 'detalle_pagos.id_deuda', DB::raw(' deuda_cuotas.id_deuda and detalle_pagos.id_servicio = cuota_servicios.id_servicio'))
            ->select(
                'deuda_cuotas.id_deuda_cuota',
                'deuda_cuotas.id_deuda',
                'servicios.nombre as nombre_servicio',
                DB::raw('max(date(deudas.fecha_registro)) as fecha'),
                DB::raw('max(year(deudas.fecha_registro)) as anio'),
                DB::raw('max((select nombre from setup_mes where setup_mes.id_mes = MONTH(deudas.fecha_registro))) AS mes'),
                DB::raw('max(deuda_cuotas.monto) as total'),
                DB::raw('max(deuda_cuotas.monto) - sum(coalesce(detalle_pagos.importe,0)) as por_pagar'),
                DB::raw('coalesce(sum(detalle_pagos.importe),0) as a_cuenta')
            );

        if ($request->has('id_puesto') && $request->id_puesto != '') {
            $query->where('deudas.id_puesto', $request->id_puesto);
        }

        if ($request->has('nombre_socio') && $request->nombre_socio != '') {
            $texto = FiltroTexto::normalizarNombre($request->nombre_socio);
            $query->join('socios', 'deudas.id_socio', 'socios.id_socio')
                ->join('personas', 'socios.id_socio', 'personas.id_persona')
                ->whereRaw('upper(personas.nombre_completo) LIKE upper(?)', ['%'.$texto.'%']);
        }

        $paginate = $query
            ->groupBy('deuda_cuotas.id_deuda_cuota', 'deuda_cuotas.id_deuda', 'servicios.nombre')
            ->havingRaw('(max(deuda_cuotas.monto) - sum(coalesce(detalle_pagos.importe,0))) > 0')
            ->paginate($per_page);

        return response()->json([
            'data' => $paginate->items(),
            'meta' => [
                'current_page' => $paginate->currentPage(),
                'last_page' => $paginate->lastPage(),
                'per_page' => $paginate->perPage(),
                'total' => $paginate->total(),
            ],
        ]);
    }

    public function registrarMultaInasistencia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_socio' => 'required',
            'id_puesto' => 'required',
            'importe' => 'required|numeric|min:0|not_in:0',
        ], [
            'id_socio.required' => 'El socio es requerido.',
            'id_puesto.required' => 'El puesto es requerido.',
            'importe.required' => 'El importe es requerido.',
            'importe.not_in' => 'El importe no puede ser 0.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Obtener o crear el servicio "Multa por inasistencia"
            $servicio = Servicio::where('nombre', 'Multa por inasistencia')->first();

            if (! $servicio) {
                $servicio = new Servicio;
                $servicio->nombre = 'Multa por inasistencia';
                $servicio->tipo_servicio = 2;
                $servicio->costo_unitario = $request->input('importe');
                $servicio->fecha_registro = date('Y-m-d');
                $servicio->save();
            } else {
                // Actualizar el costo si cambió
                if ($servicio->costo_unitario != $request->input('importe')) {
                    $servicio->costo_unitario = $request->input('importe');
                    $servicio->save();
                }
            }

            // 2. Crear la cuota para esta multa
            $cuota = new Cuota;
            $cuota->fecha_emision = date('Y-m-d');
            $cuota->fecha_vencimiento = date('Y-m-d', strtotime('+30 days'));
            $cuota->importe = $request->input('importe');
            $cuota->global = false;
            $cuota->save();

            // 3. Asignar la cuota al puesto
            $puesto_cuota = new PuestoCuota;
            $puesto_cuota->id_puesto = $request->input('id_puesto');
            $puesto_cuota->id_cuota = $cuota->id_cuota;
            $puesto_cuota->estado = 'Pendiente';
            $puesto_cuota->save();

            // 4. Registrar el servicio en la cuota
            $cuota_servicio = new CuotaServicios;
            $cuota_servicio->id_cuota = $cuota->id_cuota;
            $cuota_servicio->id_servicio = $servicio->id_servicio;
            $cuota_servicio->importe = $request->input('importe');
            $cuota_servicio->save();

            // 5. Crear una nueva deuda para esta cuota (siguiendo el patrón del sistema)
            $deuda = new Deuda;
            $deuda->id_socio = $request->input('id_socio');
            $deuda->id_puesto = $request->input('id_puesto');
            $deuda->id_cuota = $cuota->id_cuota;
            $deuda->total_deuda = $request->input('importe');
            $deuda->fecha_registro = now();
            $deuda->save();

            // 6. Registrar el detalle de la deuda
            $deuda_cuota = new DeudaCuota;
            $deuda_cuota->id_deuda = $deuda->id_deuda;
            $deuda_cuota->id_cuota_servicio = $cuota_servicio->id_cuota_servicio;
            $deuda_cuota->monto = $request->input('importe');
            $deuda_cuota->a_cuenta = 0;
            $deuda_cuota->estado = 'Pendiente';
            $deuda_cuota->save();

            DB::commit();

            return response()->json(['message' => 'Multa por inasistencia registrada correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Error al registrar la multa: '.$e->getMessage()], 500);
        }
    }
}
