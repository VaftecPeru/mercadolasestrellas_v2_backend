<?php

namespace App\Http\Controllers;

use App\Exports\CuotaExport;
use App\Exports\PDF\CuotaPDFExport;
use App\Http\Resources\CuotaCollection;
use App\Models\Cuota;
use App\Models\Deuda;
use App\Models\Socio;
use App\Models\CuotaServicios;
use App\Models\DeudaCuota;
use App\Models\Puesto;
use App\Models\Servicio;
use App\Models\DetallePagos;
use App\Models\PuestoCuota;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Util\Util;
use Carbon\Carbon;

class CuotaController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 15);
        $query = Cuota::with(['deudas', 'servicios.servicio']);

        $validator = Validator::make($request->all(), [
            'anio' => 'nullable|digits:4',
            'mes' => 'nullable|digits:1,2',  
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Parámetros "anio" o "mes" inválidos. Formato esperado: anio=YYYY, mes=M o MM'], 400);
        }

        // Aplicar filtro de año
        if ($request->filled('anio') && $request->anio !== '' && $request->anio !== null) {
            $query->whereRaw(Util::compareDateYear('fecha_emision', $request->anio));
        }

        // Aplicar filtro de mes
        if ($request->filled('mes') && $request->mes !== '' && $request->mes !== null) {
            $query->whereRaw(Util::compareDateMonth('fecha_emision', $request->mes));
        }

        return new CuotaCollection($query->paginate($per_page));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date',
            'servicios' => 'required|array|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $listado = Socio::select('socios.id_socio', 'puestos.id_puesto', 'puestos.area')
            ->join('puestos', 'puestos.id_socio', 'socios.id_socio')
            ->where('socios.estado', 1)    // Socio activo (1=activo, 0=retirado)
            ->where('puestos.activo', 1)   // Puesto activo (1=activo, 0=inactivo)
            ->get();

        if ($listado->isEmpty()) {
            return response()->json(['error' => 'No se encontraron socios con puestos.'], 400);
        }

        $servicios = Servicio::whereIn('id_servicio', $request->servicios)
            ->where('activo', 1)
            ->get();

        if ($servicios->isEmpty()) {
            return response()->json(['error' => 'No se encontraron los servicios seleccionados.'], 400);
        }

        DB::beginTransaction();

        $cuota = new Cuota();
        $cuota->fecha_emision = $request->fecha_emision;
        $cuota->fecha_vencimiento = $request->fecha_vencimiento;
        $cuota->global = true;
        $cuota->importe = 0;
        $cuota->save();

        // Crear cuota_servicios
        $cuotaServicios = [];
        foreach ($servicios as $servicio) {
            $cuota_servicio = new CuotaServicios();
            $cuota_servicio->id_cuota = $cuota->id_cuota;
            $cuota_servicio->id_servicio = $servicio->id_servicio;
            $cuota_servicio->importe = $servicio->costo_unitario;  // Usar costo base
            $cuota_servicio->save();
            $cuotaServicios[$servicio->id_servicio] = $cuota_servicio;
        }

        // Crear deudas y deuda_cuotas por cada socio
        foreach ($listado as $socio) {
            $deuda = new Deuda();
            $deuda->id_socio = $socio->id_socio;
            $deuda->id_puesto = $socio->id_puesto;
            $deuda->id_cuota = $cuota->id_cuota;
            $deuda->total_deuda = 0;
            $deuda->fecha_registro = Carbon::now();
            $deuda->save();

            foreach ($servicios as $servicio) {
                // Calcular costo por socio
                $costo_servicio = $servicio->tipo_servicio == 3
                    ? $servicio->costo_unitario * $socio->area
                    : $servicio->costo_unitario;

                $cuota->increment('importe', $costo_servicio);
                $deuda->increment('total_deuda', $costo_servicio);

                // Usar el cuota_servicio ya creado
                $deuda_cuota = new DeudaCuota();
                $deuda_cuota->id_deuda = $deuda->id_deuda;
                $deuda_cuota->id_cuota_servicio = $cuotaServicios[$servicio->id_servicio]->id_cuota_servicio;
                $deuda_cuota->monto = $costo_servicio;
                $deuda_cuota->estado = 'Pendiente';
                $deuda_cuota->a_cuenta = 0;
                $deuda_cuota->save();
            }
        }

        DB::commit();

        return response()->json(['data' => $cuota, 'message' => 'La cuota fue registrada correctamente']);
    }

    public function storePorPuesto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date',
            'id_puesto' => 'required|integer',
            'servicios' => 'required|array|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $puesto = Puesto::find($request->id_puesto);
        if (!$puesto || $puesto->estado == 0 || !$puesto->id_socio) {
            return response()->json(['error' => 'Puesto no válido o sin socio asignado.'], 400);
        }

        $socio = Socio::find($puesto->id_socio);
        if (!$socio) {
            return response()->json(['error' => 'Socio no encontrado.'], 400);
        }

        $servicios = Servicio::whereIn('id_servicio', $request->servicios)
            ->where('activo', 1)
            ->get();

        if ($servicios->isEmpty()) {
            return response()->json(['error' => 'No se encontraron servicios válidos.'], 400);
        }

        DB::beginTransaction();

        $cuota = new Cuota();
        $cuota->fecha_emision = $request->fecha_emision;
        $cuota->fecha_vencimiento = $request->fecha_vencimiento;
        $cuota->global = false;
        $cuota->importe = 0;
        $cuota->save();

        $deuda = new Deuda();
        $deuda->id_socio = $socio->id_socio;
        $deuda->id_puesto = $puesto->id_puesto;
        $deuda->id_cuota = $cuota->id_cuota;
        $deuda->total_deuda = 0;
        $deuda->fecha_registro = Carbon::now();
        $deuda->save();

        foreach ($servicios as $servicio) {
            $costo_servicio = $servicio->tipo_servicio == 3
                ? $servicio->costo_unitario * $socio->area
                : $servicio->costo_unitario;

            $cuota_servicio = new CuotaServicios();
            $cuota_servicio->id_cuota = $cuota->id_cuota;
            $cuota_servicio->id_servicio = $servicio->id_servicio;
            $cuota_servicio->importe = $costo_servicio;
            $cuota_servicio->save();

            $cuota->increment('importe', $costo_servicio);
            $deuda->increment('total_deuda', $costo_servicio);

            $deuda_cuota = new DeudaCuota();
            $deuda_cuota->id_deuda = $deuda->id_deuda;
            $deuda_cuota->id_cuota_servicio = $cuota_servicio->id_cuota_servicio;
            $deuda_cuota->monto = $costo_servicio;
            $deuda_cuota->estado = 'Pendiente';
            $deuda_cuota->a_cuenta = 0;
            $deuda_cuota->save();
        }

        DB::commit();

        return response()->json(['data' => $cuota, 'message' => 'La cuota fue registrada correctamente']);
    }

    public function export()
    {
        return Excel::download(new CuotaExport(), 'cuotas.xlsx');
    }

    public function exportPDF()
    {
        return (new CuotaPDFExport())->generatePDF();
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        DB::beginTransaction();
        try {
            $cuota = Cuota::findOrFail($id);
            $cuota->fecha_emision = $request->fecha_emision;
            $cuota->fecha_vencimiento = $request->fecha_vencimiento;
            $cuota->save();

            DB::commit();
            return response()->json(['data' => $cuota, 'message' => 'La cuota fue actualizada correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al intentar actualizar la cuota: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $cuota = Cuota::findOrFail($id);

            // Verificar si hay pagos asociados
            $tienePagos = DetallePagos::where('id_cuota', $id)->exists();
            if ($tienePagos) {
                return response()->json(['error' => 'No se puede eliminar la cuota porque tiene pagos asociados.'], 400);
            }

            // 1. Obtener IDs de deudas relacionadas
            $deudaIds = Deuda::where('id_cuota', $id)->pluck('id_deuda');

            // 2. Obtener IDs de cuota_servicios relacionados
            $cuotaServicioIds = CuotaServicios::where('id_cuota', $id)->pluck('id_cuota_servicio');

            // 3. Eliminar DeudaCuota (depende de Deuda y CuotaServicios)
            DeudaCuota::whereIn('id_deuda', $deudaIds)->delete();

            // 4. Eliminar CuotaServicios
            CuotaServicios::where('id_cuota', $id)->delete();

            // 5. Eliminar Deudas
            Deuda::where('id_cuota', $id)->delete();

            // 6. Eliminar PuestoCuota
            PuestoCuota::where('id_cuota', $id)->delete();

            // 7. Eliminar la Cuota
            $cuota->delete();

            DB::commit();
            return response()->json(['message' => 'La cuota ha sido eliminada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al intentar eliminar la cuota: ' . $e->getMessage()], 500);
        }
    }
}
