<?php

namespace App\Exports;

use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\DeudaCuota;
use App\Models\Puesto;
use App\Models\Servicio;
use App\Support\FiltroTexto;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteDeudasExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithStrictNullComparison, WithStyles
{
    protected $id_puesto;

    protected $nombre_socio;

    protected $id_socio;

    protected $encabezado = ['-', '-', '-', '-', '-'];

    private $count = 0;

    public function __construct($id_puesto, $nombre_socio = null, $id_socio = null)
    {
        $this->id_puesto = $id_puesto;
        $this->nombre_socio = $nombre_socio;
        $this->id_socio = $id_socio;
        $this->encabezado = $this->resolveEncabezado();
    }

    private function resolveEncabezado()
    {
        $default = ['-', '-', '-', '-', '-'];

        $puesto = Puesto::with(['socio.persona', 'block', 'gironegocio'])->find($this->id_puesto);

        if (! $puesto) {
            return $default;
        }

        return [
            $puesto->socio && $puesto->socio->persona ? $puesto->socio->persona->nombre_completo : '-',
            $puesto->block ? $puesto->block->nombre : '-',
            $puesto->numero_puesto ?? '-',
            $puesto->area ?? '-',
            $puesto->gironegocio ? $puesto->gironegocio->nombre : '-',
        ];
    }

    public function collection()
    {
        $deudas = Deuda::query()
            ->when($this->id_puesto, function ($query) {
                $query->where('id_puesto', $this->id_puesto);
            })
            // id_socio es el criterio autoritativo; el nombre solo se usa como respaldo
            ->when($this->id_socio, function ($query) {
                $query->where('id_socio', $this->id_socio);
            }, function ($query) {
                $query->when($this->nombre_socio, function ($query) {
                    $texto = FiltroTexto::normalizarNombre($this->nombre_socio);
                    $query->whereHas('socio.persona', function ($q) use ($texto) {
                        $q->whereRaw('upper(nombre_completo) LIKE upper(?)', ['%'.$texto.'%']);
                    });
                });
            })
            ->get()
            ->map(function ($deuda) {
                $deudaCuotas = DeudaCuota::select('c.nombre', 'c.tipo_servicio')
                    ->join('cuota_servicios as b', 'deuda_cuotas.id_cuota_servicio', 'b.id_cuota_servicio')
                    ->join('servicios as c', 'b.id_servicio', 'c.id_servicio')
                    ->where('deuda_cuotas.id_deuda', $deuda->id_deuda)
                    ->groupBy('c.nombre', 'c.tipo_servicio')->get();
                $servicio_nombres = implode(', ', $deudaCuotas->pluck('nombre')->toArray());
                $tipo_servicios = implode(', ', $deudaCuotas->map(fn ($item) => Servicio::nombreTipoServicio($item->tipo_servicio))->unique()->toArray());

                $importeSuma = DetallePagos::where('id_deuda', $deuda->id_deuda)->sum('importe');
                $importe_pagado = $importeSuma ?? 0;
                $importe_por_pagar = $deuda->total_deuda - $importe_pagado;

                return [
                    'fecha' => (new Carbon($deuda->fecha_registro))->format('Y-m-d'),
                    'servicio_descripcion' => $servicio_nombres,
                    'tipo_servicio' => $tipo_servicios ?: '-',
                    'total' => $deuda->total_deuda,
                    'importe_pagado' => $importe_pagado ?? 0,
                    'importe_por_pagar' => $importe_por_pagar,
                ];
            })
            // Solo deudas pendientes: se excluyen las totalmente canceladas
            ->filter(function ($deuda) {
                return $deuda['importe_por_pagar'] > 0;
            });

        $this->count = count($deudas);

        return $deudas;
    }

    public function headings(): array
    {
        return [
            'Fecha Pago',
            'Servicios',
            'Tipo de Servicio',
            'Total (S/.)',
            'Imp. Pagado (S/.)',
            'Imp. Por pagar (S/.)',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle(1)->getFont()->setBold(true);

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Inserta el bloque de cabecera (Nombre del socio, Bloque, Nro. Puesto, Area, Giro)
                // en las filas 1-2. La fila de encabezados pasa a la fila 3 y los datos a partir de la 4.
                $event->sheet->getDelegate()->insertNewRowBefore(1, 2);

                $labels = ['Nombre del socio', 'Bloque', 'Nro. Puesto', 'Area', 'Giro de negocio'];
                foreach ($labels as $i => $label) {
                    $column = chr(65 + $i);
                    $event->sheet->setCellValue($column.'1', $label);
                    $event->sheet->setCellValue($column.'2', $this->encabezado[$i]);
                }
                $event->sheet->getStyle('A1:E1')->getFont()->setBold(true);

                if ($this->count > 0) {
                    $lastRow = $event->sheet->getHighestRow() + 1;
                    $event->sheet->setCellValue('A'.($lastRow), 'Total (S/.)');
                    $event->sheet->mergeCells("A{$lastRow}:C{$lastRow}");
                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:F{$lastRow}")->getFont()->setBold(true);
                    $event->sheet->setCellValue('D'.($lastRow), '=SUM(D4:D'.($lastRow - 1).')');
                    $event->sheet->setCellValue('E'.($lastRow), '=SUM(E4:E'.($lastRow - 1).')');
                    $event->sheet->setCellValue('F'.($lastRow), '=SUM(F4:F'.($lastRow - 1).')');
                }
            },
        ];
    }
}
