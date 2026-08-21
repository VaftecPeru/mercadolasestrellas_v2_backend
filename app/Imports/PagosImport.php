<?php

namespace App\Imports;

use App\Models\Cuota;
use App\Models\CuotaServicios;
use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\DeudaCuota;
use App\Models\Documento;
use App\Models\Pago;
use App\Models\PagoBanco;
use App\Models\Puesto;
use App\Models\Servicio;
use App\Models\Socio;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PagosImport implements SkipsUnknownSheets, WithMultipleSheets
{
    private $importedCount = 0;

    private $errors = [];

    public function sheets(): array
    {
        $sheetRules = [];
        // Support up to 100 sheets dynamically by index
        for ($i = 0; $i < 100; $i++) {
            $sheetRules[$i] = new PagoSheetImport($this, null);
        }

        return $sheetRules;
    }

    public function onUnknownSheet($sheetName)
    {
        // Skip unknown sheets gracefully without throwing out-of-bounds error
    }

    public function incrementImportedCount()
    {
        $this->importedCount++;
    }

    public function addError($message)
    {
        $this->errors[] = $message;
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

class PagoSheetImport implements ToCollection
{
    protected $parent;

    protected $defaultYear;

    public function __construct($parent, $defaultYear = null)
    {
        $this->parent = $parent;
        $this->defaultYear = $defaultYear;
    }

    public function collection(Collection $rows)
    {
        if ($rows->count() === 0) {
            return;
        }

        // Detect if this is the mapped/flat format from frontend
        $firstRow = $rows[0]->toArray();
        if (in_array('importe', $firstRow) || in_array('puesto', $firstRow) || in_array('socio_nombre', $firstRow) || in_array('concepto', $firstRow)) {
            $headerMap = array_flip($firstRow);

            for ($i = 1; $i < $rows->count(); $i++) {
                $row = $rows[$i]->toArray();

                $nro_puesto = isset($headerMap['puesto']) ? trim($row[$headerMap['puesto']] ?? '') : '';
                $dni = isset($headerMap['dni']) ? trim($row[$headerMap['dni']] ?? '') : '';
                $nombre_socio = isset($headerMap['socio_nombre']) ? trim($row[$headerMap['socio_nombre']] ?? '') : '';
                $monto_pago = isset($headerMap['importe']) ? $this->parseAmount($row[$headerMap['importe']] ?? null) : 0.0;
                $monto_actual = isset($headerMap['monto_actual']) ? $this->parseAmount($row[$headerMap['monto_actual']] ?? null) : 0.0;
                $fecha_pago_raw = isset($headerMap['fecha_operacion']) ? ($row[$headerMap['fecha_operacion']] ?? null) : null;
                $nro_operacion = isset($headerMap['numero_operacion']) ? trim($row[$headerMap['numero_operacion']] ?? '') : 'IMPORTADO';
                $servicio_excel = isset($headerMap['concepto']) ? trim($row[$headerMap['concepto']] ?? '') : '';

                $monto_deuda = $monto_pago + $monto_actual;
                if ($monto_deuda <= 0.0) {
                    $monto_deuda = $monto_pago;
                }

                // Fila válida: concepto presente y al menos un monto (pagado o pendiente)
                if (empty($servicio_excel) || ($monto_pago <= 0.0 && $monto_actual <= 0.0)) {
                    continue;
                }

                $estado_deuda = ($monto_actual <= 0.0 && $monto_pago > 0.0) ? '0' : '1';

                try {
                    DB::beginTransaction();

                    // Find Puesto
                    $puestoObj = Puesto::where('numero_puesto', $nro_puesto)->first();
                    if (! $puestoObj) {
                        throw new \Exception("No se encontró el puesto '{$nro_puesto}'.");
                    }

                    // Find Socio — data lives in 'personas' table via join
                    $socioObj = null;
                    if ($dni) {
                        $socioObj = Socio::join('personas as p', 'socios.id_socio', 'p.id_persona')
                            ->where('p.dni', $dni)
                            ->select('socios.*')
                            ->first();
                    }
                    if (! $socioObj && $nombre_socio) {
                        $socioObj = Socio::join('personas as p', 'socios.id_socio', 'p.id_persona')
                            ->where('p.nombre_completo', 'like', '%'.$nombre_socio.'%')
                            ->select('socios.*')
                            ->first();
                    }
                    if (! $socioObj && $puestoObj->id_socio) {
                        $socioObj = Socio::find($puestoObj->id_socio);
                    }
                    if (! $socioObj) {
                        throw new \Exception("No se encontró socio para '{$nombre_socio}' / DNI '{$dni}'.");
                    }
                    $id_socio = $socioObj->id_socio;

                    // Buscar el Servicio (limpiando el sufijo de año)
                    $servicioObj = $this->findServicio($servicio_excel);
                    if (! $servicioObj) {
                        throw new \Exception("Servicio '{$servicio_excel}' no encontrado.");
                    }

                    // Determine year
                    $rowYear = date('Y');
                    if (preg_match('/\b(20\d{2})\b/', $servicio_excel, $matches)) {
                        $rowYear = intval($matches[1]);
                    }

                    // Find DeudaCuota (por servicio + año fiscal; nunca reutilizar deuda de otro año)
                    $deudaCuota = DeudaCuota::join('deudas', 'deuda_cuotas.id_deuda', '=', 'deudas.id_deuda')
                        ->join('cuota_servicios', 'deuda_cuotas.id_cuota_servicio', '=', 'cuota_servicios.id_cuota_servicio')
                        ->join('cuotas', 'cuota_servicios.id_cuota', '=', 'cuotas.id_cuota')
                        ->where('deudas.id_socio', $id_socio)
                        ->where('deudas.id_puesto', $puestoObj->id_puesto)
                        ->where('cuota_servicios.id_servicio', $servicioObj->id_servicio)
                        ->where('cuotas.id_anio', $rowYear)
                        ->select('deuda_cuotas.*')
                        ->first();

                    if (! $deudaCuota) {
                        $deudaCuota = $this->findOrCreateDeudaCuota($id_socio, $puestoObj->id_puesto, $servicioObj, $rowYear, $monto_deuda, $monto_pago, $estado_deuda);
                    }

                    $id_deuda_cuota = $deudaCuota->id_deuda_cuota;

                    if ($monto_pago > 0.0) {
                        // Duplicate check
                        $pagoExistenteQuery = DetallePagos::where('id_deuda_cuota', $id_deuda_cuota)
                            ->where('importe', $monto_pago);

                        if (! empty($nro_operacion) && $nro_operacion !== '-') {
                            $pagoExistenteQuery->whereHas('Pago.PagoBanco', function ($q) use ($nro_operacion) {
                                $q->where('numero_operacion', $nro_operacion);
                            });
                        }

                        if ($pagoExistenteQuery->exists()) {
                            DB::rollBack();

                            continue;
                        }

                        // Create Payment records
                        $documento = Documento::find(1);
                        if (! $documento) {
                            throw new \Exception('No se encontró el documento de configuración.');
                        }

                        $numeroDocumentoNuevo = $documento->numero_documento + 1;
                        $documento->numero_documento = $numeroDocumentoNuevo;
                        $documento->save();

                        $numero_pago_nuevo = str_pad($numeroDocumentoNuevo, 8, '0', STR_PAD_LEFT);

                        $fecha_pago = Carbon::now();
                        if ($fecha_pago_raw) {
                            try {
                                if (is_numeric($fecha_pago_raw)) {
                                    $fecha_pago = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha_pago_raw));
                                } else {
                                    $fecha_pago = Carbon::parse($fecha_pago_raw);
                                }
                            } catch (\Exception $e) {
                            }
                        }

                        $pago = Pago::create([
                            'id_socio' => $id_socio,
                            'id_documento' => 1,
                            'numero_pago' => $numero_pago_nuevo,
                            'serie' => $documento->serie,
                            'total_pago' => $monto_pago,
                            'fecha_registro' => $fecha_pago,
                        ]);

                        $deuda = Deuda::find($deudaCuota->id_deuda);
                        $cuotaServicios = CuotaServicios::find($deudaCuota->id_cuota_servicio);

                        DetallePagos::create([
                            'id_pago' => $pago->id_pago,
                            'id_deuda' => $deuda->id_deuda,
                            'id_deuda_cuota' => $id_deuda_cuota,
                            'id_cuota' => $cuotaServicios->id_cuota,
                            'id_puesto' => $deuda->id_puesto,
                            'id_servicio' => $cuotaServicios->id_servicio,
                            'importe' => $monto_pago,
                        ]);

                        if (! empty($nro_operacion) && $nro_operacion !== '-') {
                            PagoBanco::create([
                                'id_pagobanco' => $pago->id_pago,
                                'id_banco' => 1,
                                'id_bancocuenta' => 3,
                                'numero_operacion' => $nro_operacion,
                                'fecha_operacion' => $fecha_pago,
                            ]);
                        }

                        $this->parent->incrementImportedCount();
                    }

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->parent->addError('Fila '.($i + 1)." ({$servicio_excel}): ".$e->getMessage());
                }
            }

            return;
        }

        // ===== Formato directo por columnas (funciona con cualquier hoja) =====

        // Detectar la fila de encabezados (contiene "TODOS LOS SERVICIOS")
        $headerRowIndex = -1;
        foreach ($rows as $rIndex => $row) {
            foreach ($row->toArray() as $cell) {
                if (is_string($cell) && stripos($cell, 'TODOS LOS SERVICIOS') !== false) {
                    $headerRowIndex = $rIndex;
                    break 2;
                }
            }
        }
        if ($headerRowIndex === -1) {
            return;
        }

        // Normalizar encabezados (mayúsculas y sin tildes) para comparar por nombre de columna
        $quitarAcentos = function ($s) {
            $buscar = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ', 'À', 'È', 'Ì', 'Ò', 'Ù'];
            $reemplazar = ['A', 'E', 'I', 'O', 'U', 'U', 'N', 'A', 'E', 'I', 'O', 'U'];

            return str_replace($buscar, $reemplazar, strtoupper(trim((string) $s)));
        };

        $headerRow = $rows[$headerRowIndex]->toArray();
        $headerUpper = array_map($quitarAcentos, $headerRow);

        // Mapear el índice de cada columna según el nombre del encabezado
        $findCol = function (array $patterns, ?string $exclude = null) use ($headerUpper) {
            foreach ($headerUpper as $i => $h) {
                if ($h === '' || ($exclude !== null && strpos($h, $exclude) !== false)) {
                    continue;
                }
                foreach ($patterns as $p) {
                    if (strpos($h, $p) !== false) {
                        return $i;
                    }
                }
            }

            return -1;
        };

        $idxConcepto = $findCol(['TODOS LOS SERVICIOS', 'SERVICIO', 'CONCEPTO']);
        $idxImporteTotal = $findCol(['IMPORTE TOTAL A PAGAR', 'TOTAL A PAGAR MERCADO', 'IMPORTE TOTAL']);
        $idxMontoPagado = $findCol(['REALIZO PAGO', 'INGRESO EN SOLES', 'INGRESO X ANO']);
        $idxFecha = $findCol(['FECHA DE PAGO']);
        $idxOperacion = $findCol(['N.° OPERACION', 'N° OPERACION', 'NRO OPERACION', 'N OPERACION', 'OPERACION MERCADO']);
        $idxReporte = $findCol(['REPORTE']);
        $idxDeuda = $findCol(['DEUDA'], 'TOTAL');
        $idxAnio = $findCol(['ANO', 'AÑO']);

        // Extraer Puesto y Socio escaneando todas las celdas (posiciones dinámicas)
        $puestoRaw = null;
        $socioRaw = null;
        foreach ($rows as $row) {
            foreach ($row->toArray() as $cell) {
                if (! is_string($cell)) {
                    continue;
                }
                $cellTrim = strtoupper(trim($cell));
                if ($puestoRaw === null && strpos($cellTrim, 'PUESTO:') === 0) {
                    $puestoRaw = $cell;
                }
                if ($socioRaw === null && strpos($cellTrim, 'SOCIO:') === 0) {
                    $socioRaw = $cell;
                }
            }
        }

        // Limpiar Puesto: "PUESTO:  C1" -> "C1"
        $nro_puesto = null;
        if ($puestoRaw && preg_match('/PUESTO:\s*([A-Za-z0-9\-]+)/i', $puestoRaw, $matches)) {
            $nro_puesto = trim($matches[1]);
        }

        // Limpiar Socio: "SOCIO:  CINTHIA YULIANA LUIS CAMARENA" -> "CINTHIA YULIANA LUIS CAMARENA"
        $nombre_socio = null;
        if ($socioRaw && preg_match('/SOCIO:\s*([^|]+)/i', $socioRaw, $matches)) {
            $nombre_socio = trim($matches[1]);
        }

        if (! $nro_puesto) {
            return;
        }

        // Buscar Puesto
        $puestoObj = Puesto::where('numero_puesto', $nro_puesto)->first();
        if (! $puestoObj) {
            $this->parent->addError("Hoja (Puesto {$nro_puesto}): No se encontró el puesto '{$nro_puesto}' en la base de datos.");

            return;
        }

        // Buscar Socio — los datos viven en 'personas' vía join
        $socioObj = null;
        if ($nombre_socio) {
            $socioObj = Socio::join('personas as p', 'socios.id_socio', 'p.id_persona')
                ->where('p.nombre_completo', 'like', '%'.$nombre_socio.'%')
                ->select('socios.*')
                ->first();
        }
        if (! $socioObj && $puestoObj->id_socio) {
            $socioObj = Socio::find($puestoObj->id_socio);
        }

        if (! $socioObj) {
            $this->parent->addError("Hoja: No se encontró socio para el puesto '{$nro_puesto}'.");

            return;
        }

        $id_socio = $socioObj->id_socio;

        // Año fiscal de la hoja (primera fila de datos con año en la columna AÑO)
        $sheetYear = $this->defaultYear;
        if (! $sheetYear && $idxAnio !== -1) {
            for ($r = $headerRowIndex + 1; $r < $rows->count(); $r++) {
                $val = trim((string) ($rows[$r][$idxAnio] ?? ''));
                if (is_numeric($val) && strlen($val) === 4) {
                    $sheetYear = intval($val);
                    break;
                }
            }
        }
        if (! $sheetYear) {
            $sheetYear = date('Y');
        }

        // Iterar las filas de datos (después del encabezado)
        for ($i = $headerRowIndex + 1; $i < $rows->count(); $i++) {
            $row = $rows[$i];

            // Fila válida: servicio/concepto + al menos un valor en total o pago
            $servicio_excel = $idxConcepto !== -1 ? trim((string) ($row[$idxConcepto] ?? '')) : '';
            if (empty($servicio_excel) || stripos($servicio_excel, 'TODOS LOS SERVICIOS') !== false) {
                continue;
            }
            $servicioUpper = strtoupper(trim($servicio_excel));
            if ($servicioUpper === 'TOTAL' || $servicioUpper === 'TOTALES' || strpos($servicioUpper, 'TOTAL ') === 0) {
                continue;
            }

            $importeTotal = $idxImporteTotal !== -1 ? $this->parseAmount($row[$idxImporteTotal] ?? null) : 0.0;
            $monto_pago = $idxMontoPagado !== -1 ? $this->parseAmount($row[$idxMontoPagado] ?? null) : 0.0;

            // Ignorar filas sin montos (en blanco o sin relevancia)
            if ($importeTotal <= 0.0 && $monto_pago <= 0.0) {
                continue;
            }

            $fecha_pago_raw = $idxFecha !== -1 ? ($row[$idxFecha] ?? null) : null;
            $nro_operacion = $idxOperacion !== -1 ? trim((string) ($row[$idxOperacion] ?? '')) : '';

            // Saldo pendiente = IMPORTE TOTAL A PAGAR - REALIZO PAGO
            $pendiente = $importeTotal - $monto_pago;
            $reporteStr = $idxReporte !== -1 ? $quitarAcentos($row[$idxReporte] ?? '') : '';
            $deudaRaw = $idxDeuda !== -1 ? ($row[$idxDeuda] ?? null) : null;
            $deudaCol = $this->parseAmount($deudaRaw);
            $deudaPresente = $deudaRaw !== null && trim((string) $deudaRaw) !== '' && trim((string) $deudaRaw) !== '-';

            // Si REPORTE indica "Cancelado" o la columna DEUDA es 0, el saldo es 0 (pagado)
            if (strpos($reporteStr, 'CANCELADO') !== false || ($deudaPresente && $deudaCol <= 0.0) || $pendiente <= 0.0) {
                $pendiente = 0.0;
            }

            $monto_deuda = $importeTotal > 0.0 ? $importeTotal : $monto_pago + $pendiente;
            $estado_deuda = ($pendiente <= 0.0 && $monto_pago > 0.0) ? '0' : '1';

            // Año de la fila (columna AÑO); si no, año de la hoja
            $rowYear = $sheetYear;
            if ($idxAnio !== -1) {
                $val = trim((string) ($row[$idxAnio] ?? ''));
                if (is_numeric($val) && strlen($val) === 4) {
                    $rowYear = intval($val);
                }
            }

            try {
                DB::beginTransaction();

                // Buscar el Servicio (limpiando el sufijo de año)
                $servicioObj = $this->findServicio($servicio_excel);
                if (! $servicioObj) {
                    throw new \Exception("Servicio '{$servicio_excel}' no encontrado en la base de datos.");
                }

                // Buscar DeudaCuota por servicio + año fiscal (nunca reutilizar deuda de otro año)
                $deudaCuota = DeudaCuota::join('deudas', 'deuda_cuotas.id_deuda', '=', 'deudas.id_deuda')
                    ->join('cuota_servicios', 'deuda_cuotas.id_cuota_servicio', '=', 'cuota_servicios.id_cuota_servicio')
                    ->join('cuotas', 'cuota_servicios.id_cuota', '=', 'cuotas.id_cuota')
                    ->where('deudas.id_socio', $id_socio)
                    ->where('deudas.id_puesto', $puestoObj->id_puesto)
                    ->where('cuota_servicios.id_servicio', $servicioObj->id_servicio)
                    ->where('cuotas.id_anio', $rowYear)
                    ->select('deuda_cuotas.*')
                    ->first();

                if (! $deudaCuota) {
                    $deudaCuota = $this->findOrCreateDeudaCuota($id_socio, $puestoObj->id_puesto, $servicioObj, $rowYear, $monto_deuda, $monto_pago, $estado_deuda);
                }

                $id_deuda_cuota = $deudaCuota->id_deuda_cuota;

                if ($monto_pago > 0.0) {
                    // Verificar pago duplicado
                    $pagoExistenteQuery = DetallePagos::where('id_deuda_cuota', $id_deuda_cuota)
                        ->where('importe', $monto_pago);

                    if (! empty($nro_operacion) && $nro_operacion !== '-') {
                        $pagoExistenteQuery->whereHas('Pago.PagoBanco', function ($q) use ($nro_operacion) {
                            $q->where('numero_operacion', $nro_operacion);
                        });
                    }

                    if ($pagoExistenteQuery->exists()) {
                        DB::rollBack();

                        continue; // Saltar pago ya importado
                    }

                    $documento = Documento::find(1);
                    if (! $documento) {
                        throw new \Exception('No se encontró el documento de configuración.');
                    }

                    $numeroDocumentoNuevo = $documento->numero_documento + 1;
                    $documento->numero_documento = $numeroDocumentoNuevo;
                    $documento->save();

                    $numero_pago_nuevo = str_pad($numeroDocumentoNuevo, 8, '0', STR_PAD_LEFT);

                    // Parsear fecha
                    $fecha_pago = Carbon::now();
                    if ($fecha_pago_raw) {
                        try {
                            if (is_numeric($fecha_pago_raw)) {
                                $fecha_pago = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha_pago_raw));
                            } else {
                                $fecha_pago = Carbon::parse($fecha_pago_raw);
                            }
                        } catch (\Exception $e) {
                            // fallback
                        }
                    }

                    // Crear Pago
                    $pago = Pago::create([
                        'id_socio' => $id_socio,
                        'id_documento' => 1,
                        'numero_pago' => $numero_pago_nuevo,
                        'serie' => $documento->serie,
                        'total_pago' => $monto_pago,
                        'fecha_registro' => $fecha_pago,
                    ]);

                    $deuda = Deuda::find($deudaCuota->id_deuda);
                    $cuotaServicios = CuotaServicios::find($deudaCuota->id_cuota_servicio);

                    DetallePagos::create([
                        'id_pago' => $pago->id_pago,
                        'id_deuda' => $deuda->id_deuda,
                        'id_deuda_cuota' => $id_deuda_cuota,
                        'id_cuota' => $cuotaServicios->id_cuota,
                        'id_puesto' => $deuda->id_puesto,
                        'id_servicio' => $cuotaServicios->id_servicio,
                        'importe' => $monto_pago,
                    ]);

                    if (! empty($nro_operacion) && $nro_operacion !== '-') {
                        PagoBanco::create([
                            'id_pagobanco' => $pago->id_pago,
                            'id_banco' => 1, // BCP por defecto
                            'id_bancocuenta' => 3, // Cuenta por defecto
                            'numero_operacion' => $nro_operacion,
                            'fecha_operacion' => $fecha_pago,
                        ]);
                    }

                    $this->parent->incrementImportedCount();
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                $this->parent->addError("Hoja {$sheetYear}, Fila ".($i + 1)." ({$servicio_excel}): ".$e->getMessage());
            }
        }
    }

    /**
     * Limpia el nombre del servicio del Excel (quita el sufijo de año, ej. " - 2023")
     * y lo busca en la base de datos con coincidencia progresiva: prefijo, contenido y normalizada.
     * Retorna el modelo Servicio o null si no hay coincidencia.
     */
    private function findServicio($servicio_excel)
    {
        $servicio_limpio = trim(preg_replace('/\s*-\s*\d{4}\s*$/', '', $servicio_excel));

        $servicioObj = Servicio::where('nombre', 'like', $servicio_limpio.'%')->first();
        if (! $servicioObj) {
            $servicioObj = Servicio::where('nombre', 'like', '%'.$servicio_limpio.'%')->first();
        }

        if (! $servicioObj) {
            $normalizedLimpio = preg_replace('/[^a-zA-Z0-9]/', '', $servicio_limpio);
            foreach (Servicio::all() as $s) {
                $normalizedDb = preg_replace('/[^a-zA-Z0-9]/', '', $s->nombre);
                if (stripos($normalizedDb, $normalizedLimpio) !== false || stripos($normalizedLimpio, $normalizedDb) !== false) {
                    return $s;
                }
            }
        }

        return $servicioObj;
    }

    /**
     * Busca la deuda (DeudaCuota) del socio/puesto/servicio/año. Si no existe,
     * la genera automáticamente (Deuda + DeudaCuota) antes de registrar el pago.
     *
     * $montoTotal = IMPORTE TOTAL A PAGAR; $montoPagado = REALIZO PAGO (A Cuenta).
     */
    private function findOrCreateDeudaCuota($idSocio, $idPuesto, $servicioObj, $rowYear, $montoTotal, $montoPagado = 0.0, $estado = '1')
    {
        // 1. Localizar la cuota_servicios del servicio para el año fiscal
        $cuotaServicios = CuotaServicios::join('cuotas', 'cuota_servicios.id_cuota', '=', 'cuotas.id_cuota')
            ->where('cuota_servicios.id_servicio', $servicioObj->id_servicio)
            ->where('cuotas.id_anio', $rowYear)
            ->select('cuota_servicios.*')
            ->first();

        // 2. Fallback: cualquier cuota_servicios del servicio
        if (! $cuotaServicios) {
            $cuotaServicios = CuotaServicios::where('id_servicio', $servicioObj->id_servicio)->first();
        }

        // 3. Si tampoco existe, crear cuota + cuota_servicios
        if (! $cuotaServicios) {
            $cuota = new Cuota;
            $cuota->fecha_emision = Carbon::now();
            $cuota->fecha_vencimiento = Carbon::now();
            $cuota->global = '1';
            $cuota->importe = $montoTotal;
            $cuota->id_anio = $rowYear;
            $cuota->save();

            $cuotaServicios = new CuotaServicios;
            $cuotaServicios->id_cuota = $cuota->id_cuota;
            $cuotaServicios->id_servicio = $servicioObj->id_servicio;
            $cuotaServicios->importe = $montoTotal;
            $cuotaServicios->save();
        }

        // 4. Buscar o crear la Deuda del socio/puesto para esa cuota
        $deuda = Deuda::where('id_socio', $idSocio)
            ->where('id_puesto', $idPuesto)
            ->where('id_cuota', $cuotaServicios->id_cuota)
            ->first();

        $deudaExistia = $deuda !== null;

        if (! $deuda) {
            $deuda = new Deuda;
            $deuda->id_socio = $idSocio;
            $deuda->id_puesto = $idPuesto;
            $deuda->id_cuota = $cuotaServicios->id_cuota;
            $deuda->total_deuda = $montoTotal;
            $deuda->fecha_registro = Carbon::now();
            $deuda->save();
        }

        // 5. Buscar o crear la DeudaCuota
        $deudaCuota = DeudaCuota::where('id_deuda', $deuda->id_deuda)
            ->where('id_cuota_servicio', $cuotaServicios->id_cuota_servicio)
            ->first();

        if (! $deudaCuota) {
            $deudaCuota = new DeudaCuota;
            $deudaCuota->id_deuda = $deuda->id_deuda;
            $deudaCuota->id_cuota_servicio = $cuotaServicios->id_cuota_servicio;
            $deudaCuota->monto = $montoTotal;
            $deudaCuota->a_cuenta = $montoPagado;
            $deudaCuota->estado = $estado;
            $deudaCuota->save();

            // Si la deuda ya existía (se reutiliza), sumar el nuevo monto al total
            if ($deudaExistia) {
                $deuda->increment('total_deuda', $montoTotal);
            }
        } elseif ($deudaCuota->a_cuenta < $montoPagado) {
            // Reutilizada: reflejar el monto abonado (A Cuenta) sin reducir
            $deudaCuota->a_cuenta = $montoPagado;
            $deudaCuota->save();
        }

        return $deudaCuota;
    }

    /**
     * Convierte un valor de celda en número decimal de forma segura.
     * Acepta números, "1,388.96" y "S/ 1,388.96".
     */
    private function parseAmount($value)
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $str = str_replace(',', '', trim((string) $value));
        $str = preg_replace('/[^0-9.]/', '', $str);

        return (float) $str;
    }
}
