<?php

namespace App\Http\Controllers;

use App\Exports\PagosExport;
use App\Exports\PDF\PagosPDFExport;
use App\Models\Pago;
use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\UpdatePagoRequest;
use App\Http\Resources\PagoCollection;
use App\Models\PagoBanco;
use App\Models\Cuota;
use App\Models\CuotaServicios;
use App\Models\DeudaCuota;
use App\Models\Puesto;
use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\Documento;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 15;
        $paginate = Pago::with('PagoBanco')->orderBy('fecha_registro', 'desc')->paginate($per_page);

        return new PagoCollection($paginate);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_socio' => 'required',
            'id_documento' => 'nullable',
            'deudas' => 'required|array|min:1',
            'deudas.*.id_deuda_cuota' => 'required',
            'deudas.*.importe' => 'required|numeric|min:0|not_in:0',
        ], [
            'id_socio.required' => 'El id del socio es requerido.',
            'id_documento.required' => 'El documento es requerido.',
            'deudas.required' => 'No se han seleccionado deudas.',
            'deudas.*.id_deuda_cuota.required' => 'No se recibió el id de la deuda.',
            'deudas.*.importe.required' => 'No se recibió el importe de la deuda.',
            'deudas.*.importe.not_in' => 'El importe de la deuda no puede ser 0.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $documento = Documento::find($request->id_documento ?? 1);
        if (!$documento) {
            return response()->json(['error' => 'No se encontro el documento.'], 400);
        }

        $no_validos = "";

        foreach ($request->input('deudas') as $deuda_value) {
            $deudaCuota = DeudaCuota::find($deuda_value['id_deuda_cuota']);
            $importe_a_cuenta = DetallePagos::where('id_deuda_cuota',$deuda_value['id_deuda_cuota'])->sum("importe");
            $importe_a_cuenta = $importe_a_cuenta ?? 0;
            $resto_de_deuda = $deudaCuota->monto - $importe_a_cuenta;

            if(!($resto_de_deuda >= $deuda_value['importe'])){
                $no_validos .= "#".$deuda_value['id_deuda_cuota']." ".$deuda_value['importe']." ";
            }
        }
        if($no_validos != ""){
            return response()->json(['error' => 'No se recibieron deudas válidas ('.$no_validos.').'], 400);
        }

        DB::beginTransaction();

        $numeroDocumentoNuevo = $documento->numero_documento + 1;
        $documento->numero_documento = $numeroDocumentoNuevo;
        $documento->update();

        $numero_pago_nuevo = str_pad($numeroDocumentoNuevo, 8, '0', STR_PAD_LEFT);

        $pago = new Pago();
        $pago->id_socio = $request->input('id_socio');
        $pago->id_documento = $documento->id_documento;
        $pago->numero_pago = $numero_pago_nuevo;
        $pago->serie = $documento->serie;
        $pago->total_pago = 0;
        $pago->fecha_registro = Carbon::now();
        $pago->save();

        foreach ($request->input('deudas') as $deuda_value) {
            $deudaCuota = DeudaCuota::find($deuda_value['id_deuda_cuota']);
            $deuda = Deuda::find($deudaCuota->id_deuda);
            $cuotaServicios = CuotaServicios::find($deudaCuota->id_cuota_servicio);

            $detallePagos = new DetallePagos();
            $detallePagos->id_pago = $pago->id_pago;
            $detallePagos->id_deuda = $deuda->id_deuda;
            $detallePagos->id_deuda_cuota = $deuda_value['id_deuda_cuota'];
            $detallePagos->id_cuota = $cuotaServicios->id_cuota;
            $detallePagos->id_puesto = $deuda->id_puesto;
            $detallePagos->id_servicio = $cuotaServicios->id_servicio;
            $detallePagos->importe = $deuda_value['importe'];
            $detallePagos->save();
        }
        $sumaImporte = DetallePagos::where('id_pago',$pago->id_pago)->sum('importe');
        $pago->total_pago = $sumaImporte;
        $pago->save();

        DB::commit();

        return response()->json(['data' => $pago, 'message' => 'El pago fue registrado con exito'], 200);
    }

    public function storePagoPorBanco(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_socio' => 'required',
            'id_documento' => 'nullable',
            'id_banco' => 'required',
            'id_bancocuenta' => 'required',
            'numero_operacion' => 'required',
            'fecha_operacion' => 'required',
            'deudas' => 'required|array|min:1',
            'deudas.*.id_deuda_cuota' => 'required',
            'deudas.*.importe' => 'required|numeric|min:0|not_in:0',
        ], [
            'id_socio.required' => 'El socio es requerido.',
            'id_documento.required' => 'El documento es requerido.',
            'id_banco.required' => 'El banco es requerido.',
            'id_bancocuenta.required' => 'La cuenta es requerido.',
            'numero_operacion.required' => 'El número de operación es requerido.',
            'fecha_operacion.required' => 'La fecha de operación es requerido.',
            'deudas.required' => 'No se han seleccionado deudas.',
            'deudas.*.id_deuda_cuota.required' => 'No se recibió el id de la deuda.',
            'deudas.*.importe.required' => 'No se recibió el importe de la deuda.',
            'deudas.*.importe.not_in' => 'El importe de la deuda no puede ser 0.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $documento = Documento::find($request->id_documento ?? 1);
        if (!$documento) {
            return response()->json(['error' => 'No se encontro el documento.'], 400);
        }

        $no_validos = "";

        foreach ($request->input('deudas') as $deuda_value) {
            $deudaCuota = DeudaCuota::find($deuda_value['id_deuda_cuota']);
            $importe_a_cuenta = DetallePagos::where('id_deuda_cuota',$deuda_value['id_deuda_cuota'])->sum("importe");
            $importe_a_cuenta = $importe_a_cuenta ?? 0;
            $resto_de_deuda = $deudaCuota->monto - $importe_a_cuenta;

            if(!($resto_de_deuda >= $deuda_value['importe'])){
                $no_validos .= "#".$deuda_value['id_deuda_cuota']." ".$deuda_value['importe']." ";
            }
        }
        if($no_validos != ""){
            return response()->json(['error' => 'No se recibieron deudas válidas ('.$no_validos.').'], 400);
        }

        DB::beginTransaction();

        $numeroDocumentoNuevo = $documento->numero_documento + 1;
        $documento->numero_documento = $numeroDocumentoNuevo;
        $documento->update();

        $numero_pago_nuevo = str_pad($numeroDocumentoNuevo, 8, '0', STR_PAD_LEFT);

        $pago = new Pago();
        $pago->id_socio = $request->input('id_socio');
        $pago->id_documento = $documento->id_documento;
        $pago->numero_pago = $numero_pago_nuevo;
        $pago->serie = $documento->serie;
        $pago->total_pago = 0;
        $pago->fecha_registro = Carbon::now();
        $pago->save();

        $pagoBanco = new PagoBanco();
        $pagoBanco->id_pagobanco = $pago->id_pago;
        $pagoBanco->id_banco = $request->input('id_banco');
        $pagoBanco->id_bancocuenta = $request->input('id_bancocuenta');
        $pagoBanco->numero_operacion = $request->input('numero_operacion');
        $pagoBanco->fecha_operacion = $request->input('fecha_operacion');
        $pagoBanco->save();

        foreach ($request->input('deudas') as $deuda_value) {
            $deudaCuota = DeudaCuota::find($deuda_value['id_deuda_cuota']);
            $deuda = Deuda::find($deudaCuota->id_deuda);
            $cuotaServicios = CuotaServicios::find($deudaCuota->id_cuota_servicio);

            $detallePagos = new DetallePagos();
            $detallePagos->id_pago = $pago->id_pago;
            $detallePagos->id_deuda = $deuda->id_deuda;
            $detallePagos->id_deuda_cuota = $deuda_value['id_deuda_cuota'];
            $detallePagos->id_cuota = $cuotaServicios->id_cuota;
            $detallePagos->id_puesto = $deuda->id_puesto;
            $detallePagos->id_servicio = $cuotaServicios->id_servicio;
            $detallePagos->importe = $deuda_value['importe'];
            $detallePagos->save();
        }
        $sumaImporte = DetallePagos::where('id_pago',$pago->id_pago)->sum('importe');
        $pago->total_pago = $sumaImporte;
        $pago->save();

        DB::commit();

        return response()->json(['data' => $pago, 'message' => 'El pago fue registrado con exito'], 200);
    }

    public function ListaDeudaCuotas($id_puesto)
    {
        $deuda_cuota = DeudaCuota::select(
                'deuda_cuotas.a_cuenta',
                'cuotas.fecha_registro',
                'servicios.descripcion as servicio',
                'cuotas.importe'
            )
            ->join('cuotas', 'deuda_cuotas.id_cuota', '=', 'cuotas.id_cuota')
            ->join('puesto_cuotas', 'cuotas.id_cuota', '=', 'puesto_cuotas.id_cuota')
            ->join('servicios', 'cuotas.id_servicio', '=', 'servicios.id_servicio')
            ->where('puesto_cuotas.id_puesto', $id_puesto)
            ->get();

        return response()->json($deuda_cuota);
    }

    public function export()
    {
        return Excel::download(new PagosExport(), 'pagos.xlsx');
    }

    public function exportPDF()
    {
        $export = new PagosPDFExport();
        return $export->generatePDF();
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha_registro' => 'required|date',
            'id_banco' => 'nullable',
            'id_bancocuenta' => 'nullable',
            'numero_operacion' => 'nullable',
            'fecha_operacion' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        DB::beginTransaction();
        try {
            $pago = Pago::findOrFail($id);
            $pago->fecha_registro = $request->fecha_registro;
            $pago->save();

            // Si se envían datos de banco, actualizamos o creamos el registro relacionado
            if ($request->filled('id_banco')) {
                $pagoBanco = PagoBanco::where('id_pagobanco', $id)->first();
                if (!$pagoBanco) {
                    $pagoBanco = new PagoBanco();
                    $pagoBanco->id_pagobanco = $id;
                }
                $pagoBanco->id_banco = $request->id_banco;
                $pagoBanco->id_bancocuenta = $request->id_bancocuenta;
                $pagoBanco->numero_operacion = $request->numero_operacion;
                $pagoBanco->fecha_operacion = $request->fecha_operacion;
                $pagoBanco->save();
            }

            DB::commit();
            return response()->json(['data' => $pago, 'message' => 'El pago fue actualizado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al intentar actualizar el pago: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $pago = Pago::findOrFail($id);

            // 1. Eliminar PagoBanco si existe
            PagoBanco::where('id_pagobanco', $id)->delete();

            // 2. Eliminar DetallePagos
            DetallePagos::where('id_pago', $id)->delete();

            // 3. Eliminar el Pago
            $pago->delete();

            DB::commit();
            return response()->json(['message' => 'El pago ha sido eliminado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al intentar eliminar el pago: ' . $e->getMessage()], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new \App\Imports\PagosImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Importación finalizada.',
            'imported_count' => $import->getImportedCount(),
            'errors' => $import->getErrors()
        ], 200);
    }
}

