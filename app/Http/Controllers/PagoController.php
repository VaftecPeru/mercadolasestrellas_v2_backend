<?php

namespace App\Http\Controllers;

use App\Exports\PagosExport;
use App\Models\BancoCuenta;
use App\Exports\PDF\PagosPDFExport;
use App\Http\Resources\PagoCollection;
use App\Models\CuotaServicios;
use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\DeudaCuota;
use App\Models\Documento;
use App\Models\Pago;
use App\Models\PagoBanco;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $per_page = 15;

        if (isset($request->per_page)) {
            $per_page = $request->per_page;
        }

        $paginate = Pago::with(['socio.persona']) // Cargar relaciones para evitar N+1
            ->select('pagos.*')
            ->join('socios', 'pagos.id_socio', 'socios.id_socio')
            ->join('personas', 'socios.id_socio', 'personas.id_persona')
            ->orderBy('pagos.fecha_registro', 'desc');

        // Filtro de búsqueda por nombre del socio
        if (isset($request->search) && ! empty($request->search)) {
            $texto = strtr(utf8_decode($request->search), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $texto = strtr(utf8_decode($texto), utf8_decode('àáâãäçèéêëìíîïññòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiin?ooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $texto = str_replace(' ', '%', $texto);
            $paginate->whereRaw('upper(personas.nombre_completo) LIKE upper(?)', ['%'.$texto.'%']);
        }

        // Filtro por puesto (via detalle_pagos)
        if (isset($request->id_puesto) && $request->id_puesto !== '') {
            $paginate->whereHas('DetallePagos', function ($q) use ($request) {
                $q->where('id_puesto', $request->id_puesto);
            });
        }

        return new PagoCollection($paginate->paginate($per_page));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_socio' => 'required|integer',
            'deudas' => 'required|array|min:1',
            'deudas.*.id_deuda_cuota' => 'required|integer|distinct',
            'deudas.*.importe' => 'required|numeric|gt:0',
        ], [
            'id_socio.required' => 'El id del socio es requerido.',
            'deudas.required' => 'No se han seleccionado deudas.',
            'deudas.*.id_deuda_cuota.required' => 'No se recibió el id de la deuda.',
            'deudas.*.id_deuda_cuota.distinct' => 'No se puede registrar la misma deuda más de una vez en el pago.',
            'deudas.*.importe.required' => 'No se recibió el importe de la deuda.',
            'deudas.*.importe.gt' => 'El importe de la deuda debe ser mayor a 0.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            $pago = $this->registrarPagoTransaccional(
                (int) $request->input('id_socio'),
                $request->input('deudas')
            );

            return response()->json([
                'data' => $pago,
                'message' => 'El pago fue registrado con exito',
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error al registrar el pago.'], 500);
        }
    }

    public function storePagoPorBanco(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_socio' => 'required|integer',
            'id_banco' => 'required',
            'id_bancocuenta' => 'required',
            'numero_operacion' => 'required',
            'fecha_operacion' => 'required|date',
            'deudas' => 'required|array|min:1',
            'deudas.*.id_deuda_cuota' => 'required|integer|distinct',
            'deudas.*.importe' => 'required|numeric|gt:0',
        ], [
            'id_socio.required' => 'El socio es requerido.',
            'id_banco.required' => 'El banco es requerido.',
            'id_bancocuenta.required' => 'La cuenta es requerida.',
            'numero_operacion.required' => 'El número de operación es requerido.',
            'fecha_operacion.required' => 'La fecha de operación es requerida.',
            'deudas.required' => 'No se han seleccionado deudas.',
            'deudas.*.id_deuda_cuota.required' => 'No se recibió el id de la deuda.',
            'deudas.*.id_deuda_cuota.distinct' => 'No se puede registrar la misma deuda más de una vez en el pago.',
            'deudas.*.importe.required' => 'No se recibió el importe de la deuda.',
            'deudas.*.importe.gt' => 'El importe de la deuda debe ser mayor a 0.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            $pago = $this->registrarPagoTransaccional(
                (int) $request->input('id_socio'),
                $request->input('deudas'),
                [
                    'id_banco' => $request->input('id_banco'),
                    'id_bancocuenta' => $request->input('id_bancocuenta'),
                    'numero_operacion' => $request->input('numero_operacion'),
                    'fecha_operacion' => $request->input('fecha_operacion'),
                ]
            );

            return response()->json([
                'data' => $pago,
                'message' => 'El pago fue registrado con exito',
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error al registrar el pago por banco.'], 500);
        }
    }

    /**
     * Registra un pago de forma atómica y serializa la validación del saldo.
     * Esto evita pagos cruzados entre socios, deudas duplicadas y sobrepagos
     * cuando dos operaciones se ejecutan de manera concurrente.
     */
    private function registrarPagoTransaccional(
        int $idSocio,
        array $deudasSolicitadas,
        ?array $datosBanco = null
    ): Pago {
        return DB::transaction(function () use ($idSocio, $deudasSolicitadas, $datosBanco) {
            $documento = Documento::where('id_documento', 1)
                ->lockForUpdate()
                ->first();

            if (! $documento) {
                throw new \InvalidArgumentException('No se encontro el documento.');
            }

            $deudasValidadas = [];

            foreach ($deudasSolicitadas as $deudaValue) {
                $idDeudaCuota = (int) $deudaValue['id_deuda_cuota'];
                $importeSolicitado = round((float) $deudaValue['importe'], 2);

                $deudaCuota = DeudaCuota::where('id_deuda_cuota', $idDeudaCuota)
                    ->lockForUpdate()
                    ->first();

                if (! $deudaCuota) {
                    throw new \InvalidArgumentException(
                        'La deuda seleccionada #'.$idDeudaCuota.' no existe.'
                    );
                }

                $deuda = Deuda::find($deudaCuota->id_deuda);

                if (! $deuda || (int) $deuda->id_socio !== $idSocio) {
                    throw new \InvalidArgumentException(
                        'Una de las deudas seleccionadas no pertenece al socio indicado.'
                    );
                }

                $cuotaServicios = CuotaServicios::find($deudaCuota->id_cuota_servicio);

                if (! $cuotaServicios) {
                    throw new \InvalidArgumentException(
                        'La deuda seleccionada no tiene un servicio asociado válido.'
                    );
                }

                $importePagado = DetallePagos::where('id_deuda_cuota', $idDeudaCuota)
                    ->lockForUpdate()
                    ->get(['importe'])
                    ->sum('importe');

                $saldoPendiente = round(
                    (float) $deudaCuota->monto - (float) $importePagado,
                    2
                );

                if ($importeSolicitado <= 0 || $importeSolicitado > $saldoPendiente) {
                    throw new \InvalidArgumentException(
                        'El importe solicitado supera el saldo pendiente de la deuda #'.$idDeudaCuota.'.'
                    );
                }

                $deudasValidadas[] = [
                    'deuda_cuota' => $deudaCuota,
                    'deuda' => $deuda,
                    'cuota_servicio' => $cuotaServicios,
                    'importe' => $importeSolicitado,
                ];
            }

            $documento->numero_documento = (int) $documento->numero_documento + 1;
            $documento->save();

            $pago = new Pago;
            $pago->id_socio = $idSocio;
            $pago->id_documento = 1;
            $pago->numero_pago = str_pad(
                (string) $documento->numero_documento,
                8,
                '0',
                STR_PAD_LEFT
            );
            $pago->serie = $documento->serie;
            $pago->total_pago = 0;
            $pago->fecha_registro = Carbon::now();
            $pago->save();

            if ($datosBanco !== null) {
                $cuentaBancoValida = BancoCuenta::where(
                    'id_bancocuenta',
                    $datosBanco['id_bancocuenta']
                )
                    ->where('id_banco', $datosBanco['id_banco'])
                    ->where('estado', '1')
                    ->exists();

                if (! $cuentaBancoValida) {
                    throw new \InvalidArgumentException(
                        'La cuenta bancaria seleccionada no pertenece al banco indicado o está inactiva.'
                    );
                }

                $pagoBanco = new PagoBanco;
                $pagoBanco->id_pagobanco = $pago->id_pago;
                $pagoBanco->id_banco = $datosBanco['id_banco'];
                $pagoBanco->id_bancocuenta = $datosBanco['id_bancocuenta'];
                $pagoBanco->numero_operacion = $datosBanco['numero_operacion'];
                $pagoBanco->fecha_operacion = $datosBanco['fecha_operacion'];
                $pagoBanco->save();
            }

            $totalPago = 0;

            foreach ($deudasValidadas as $item) {
                /** @var DeudaCuota $deudaCuota */
                $deudaCuota = $item['deuda_cuota'];
                /** @var Deuda $deuda */
                $deuda = $item['deuda'];
                /** @var CuotaServicios $cuotaServicios */
                $cuotaServicios = $item['cuota_servicio'];

                $detallePagos = new DetallePagos;
                $detallePagos->id_pago = $pago->id_pago;
                $detallePagos->id_deuda = $deuda->id_deuda;
                $detallePagos->id_deuda_cuota = $deudaCuota->id_deuda_cuota;
                $detallePagos->id_cuota = $cuotaServicios->id_cuota;
                $detallePagos->id_puesto = $deuda->id_puesto;
                $detallePagos->id_servicio = $cuotaServicios->id_servicio;
                $detallePagos->importe = $item['importe'];
                $detallePagos->save();

                $totalPago += $item['importe'];
            }

            $pago->total_pago = round($totalPago, 2);
            $pago->save();

            return $pago;
        }, 3);
    }

    public function ListaDeudaCuotas($id_puesto)
    {

        $deuda_cuota = DeudaCuota::select('deuda_cuotas.a_cuenta', 'cuotas.fecha_emision', 'servicios.descripcion as servicio', 'cuotas.importe')

            ->join('cuotas', 'deuda_cuotas.id_cuota', '=', 'cuotas.id_cuota')

            ->join('puesto_cuotas', 'cuotas.id_cuota', '=', 'puesto_cuotas.id_cuota')

            ->join('servicios', 'cuotas.id_servicio', '=', 'servicios.id_servicio')

            ->where('puesto_cuotas.id_puesto', $id_puesto)

            ->get();

        return response()->json($deuda_cuota);

    }

    public function export()
    {

        return Excel::download(new PagosExport, 'pagos.xlsx');

    }

    public function exportPDF()
    {

        $export = new PagosPDFExport;

        return $export->generatePDF();

    }

    public function import(Request $request)
    {

        $request->validate([

            'file' => 'required|mimes:xlsx,xls,csv',

        ]);

        $import = new \App\Imports\PagosImport;

        Excel::import($import, $request->file('file'));

        return response()->json([

            'message' => 'Importación finalizada.',

            'imported_count' => $import->getImportedCount(),

            'errors' => $import->getErrors(),

        ], 200);

    }

    public function update(Request $request, Pago $pago)
    {

        $validator = Validator::make($request->all(), [

            'fecha_registro' => 'required|date',

        ]);

        if ($validator->fails()) {

            return response()->json(['error' => $validator->errors()->first()], 400);

        }

        DB::beginTransaction();

        try {

            $pago->fecha_registro = $request->fecha_registro;

            $pago->save();

            if ($pago->PagoBanco && $request->has('numero_operacion')) {

                $pagoBanco = $pago->PagoBanco;

                $pagoBanco->id_banco = $request->input('id_banco', $pagoBanco->id_banco);

                $pagoBanco->id_bancocuenta = $request->input('id_bancocuenta', $pagoBanco->id_bancocuenta);

                $pagoBanco->numero_operacion = $request->input('numero_operacion', $pagoBanco->numero_operacion);

                $pagoBanco->fecha_operacion = $request->input('fecha_operacion', $pagoBanco->fecha_operacion);

                $pagoBanco->save();

            }

            DB::commit();

            return response()->json(['data' => $pago, 'message' => 'El pago fue actualizado con éxito'], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json(['error' => 'Error al actualizar el pago.'], 500);

        }

    }

    public function destroy(Pago $pago)
    {
        DB::beginTransaction();
        try {
            $pago->DetallePagos()->delete();
            if ($pago->PagoBanco) {
                $pago->PagoBanco()->delete();
            }
            $pago->delete();

            // Sincronizar el contador de documentos para que la secuencia sea correcta
            $documento = Documento::find(1);
            if ($documento) {
                $ultimoPago = Pago::orderBy('numero_pago', 'desc')->first();
                $documento->numero_documento = $ultimoPago ? (int) $ultimoPago->numero_pago : 0;
                $documento->save();
            }

            DB::commit();

            return response()->json(['message' => 'El pago ha sido eliminado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Error al eliminar el pago.'], 500);
        }
    }
}
