<?php

namespace App\Imports;

use App\Models\Pago;
use App\Models\PagoBanco;
use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\DeudaCuota;
use App\Models\CuotaServicios;
use App\Models\Documento;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

use App\Models\Socio;
use App\Models\Puesto;

class PagosImport implements ToCollection, WithHeadingRow, WithValidation
{
    private $importedCount = 0;
    private $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                DB::beginTransaction();

                // 1. Obtener documento para correlativo
                $documento = Documento::find(1);
                if (!$documento) {
                    throw new \Exception("No se encontró el documento de configuración.");
                }

                $id_socio = $row['id_socio'] ?? null;
                $id_deuda_cuota = $row['id_deuda_cuota'] ?? null;
                $dni = $row['dni'] ?? null;
                $nro_puesto = $row['puesto'] ?? null;

                // 2. Lookup Socio if missing
                if (!$id_socio && $dni) {
                    $socio = Socio::where('dni', $dni)->first();
                    if ($socio) {
                        $id_socio = $socio->id_socio;
                    }
                }

                // New Logic: Lookup Socio by Puesto if still missing
                if (!$id_socio && $nro_puesto) {
                    $puestoObj = Puesto::where('numero_puesto', $nro_puesto)->first();
                    if ($puestoObj && $puestoObj->id_socio) {
                        $id_socio = $puestoObj->id_socio;
                    }
                }

                if (!$id_socio) {
                    throw new \Exception("No se pudo identificar al socio (falta id_socio o dni válido).");
                }

                // 3. Lookup DeudaCuota if missing
                if (!$id_deuda_cuota) {
                    $query = DeudaCuota::join('deudas', 'deuda_cuotas.id_deuda', '=', 'deudas.id_deuda')
                        ->where('deudas.id_socio', $id_socio)
                        // ->where('deuda_cuotas.estado', 'P') // P de Pendiente. Lo comentamos para permitir registros históricos o pagos de deudas ya cerradas
                        ->select('deuda_cuotas.*');

                    if ($nro_puesto) {
                        $puesto = Puesto::where('numero_puesto', $nro_puesto)->first();
                        if ($puesto) {
                            $query->where('deudas.id_puesto', $puesto->id_puesto);
                        }
                    }

                    // Tomamos la deuda más antigua
                    $deudaCuota = $query->join('cuota_servicios', 'deuda_cuotas.id_cuota_servicio', '=', 'cuota_servicios.id_cuota_servicio')
                        ->join('cuotas', 'cuota_servicios.id_cuota', '=', 'cuotas.id_cuota')
                        ->orderBy('cuotas.fecha_emision', 'asc')
                        ->first();

                    if (!$deudaCuota) {
                        throw new \Exception("No se encontraron deudas pendientes para el socio.");
                    }
                    $id_deuda_cuota = $deudaCuota->id_deuda_cuota;
                } else {
                    $deudaCuota = DeudaCuota::find($id_deuda_cuota);
                    if (!$deudaCuota) {
                        throw new \Exception("ID de Deuda Cuota #{$id_deuda_cuota} no existe.");
                    }
                }

                // Calcular deuda restante
                $importe_a_cuenta = DetallePagos::where('id_deuda_cuota', $id_deuda_cuota)->sum('importe') ?? 0;
                $restante = $deudaCuota->monto - $importe_a_cuenta;

                // Relaxed validation: Allow payments even if they exceed the remaining debt (e.g., for historical records)
                // if ($row['importe'] > $restante) {
                //     throw new \Exception("El importe ({$row['importe']}) excede la deuda restante ({$restante}) para la deuda #{$id_deuda_cuota}.");
                // }

                // 4. Generar número de pago
                $numeroDocumentoNuevo = $documento->numero_documento + 1;
                $documento->numero_documento = $numeroDocumentoNuevo;
                $documento->save();

                $numero_pago_nuevo = str_pad($numeroDocumentoNuevo, 8, '0', STR_PAD_LEFT);

                // 5. Crear Pago
                $pago = Pago::create([
                    'id_socio' => $id_socio,
                    'id_documento' => 1,
                    'numero_pago' => $numero_pago_nuevo,
                    'serie' => $documento->serie,
                    'total_pago' => $row['importe'],
                    'fecha_registro' => Carbon::now(),
                ]);

                // 6. Crear Pago Detalle
                $deuda = Deuda::find($deudaCuota->id_deuda);
                $cuotaServicios = CuotaServicios::find($deudaCuota->id_cuota_servicio);

                DetallePagos::create([
                    'id_pago' => $pago->id_pago,
                    'id_deuda' => $deuda->id_deuda,
                    'id_deuda_cuota' => $id_deuda_cuota,
                    'id_cuota' => $cuotaServicios->id_cuota,
                    'id_puesto' => $deuda->id_puesto,
                    'id_servicio' => $cuotaServicios->id_servicio,
                    'importe' => $row['importe'],
                ]);

                // 7. Si es pago por banco, crear PagoBanco
                if (!empty($row['id_banco']) && !empty($row['id_bancocuenta'])) {
                    PagoBanco::create([
                        'id_pagobanco' => $pago->id_pago,
                        'id_banco' => $row['id_banco'],
                        'id_bancocuenta' => $row['id_bancocuenta'],
                        'numero_operacion' => $row['numero_operacion'] ?? 'IMPORTADO',
                        'fecha_operacion' => !empty($row['fecha_operacion']) ? Carbon::parse($row['fecha_operacion']) : Carbon::now(),
                    ]);
                }

                DB::commit();
                $this->importedCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    public function rules(): array
    {
        return [
            'id_socio' => 'nullable',
            'dni' => 'nullable',
            'id_deuda_cuota' => 'nullable',
            'importe' => 'required|numeric|min:0.01',
            'id_banco' => 'nullable',
            'id_bancocuenta' => 'nullable',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'importe.required' => 'El importe es obligatorio.',
            'importe.numeric' => 'El importe debe ser un número.',
        ];
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
