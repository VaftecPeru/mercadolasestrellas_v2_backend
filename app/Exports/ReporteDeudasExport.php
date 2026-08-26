<?php

namespace App\Exports;

use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\DeudaCuota;
use App\Models\Puesto;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReporteDeudasExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithStrictNullComparison
{
    protected $id_puesto;
    protected $encabezado = ['-', '-', '-', '-', '-'];
    private $count = 0;

    public function __construct($id_puesto)
    {
        $this->id_puesto = $id_puesto;
        $this->encabezado = $this->resolveEncabezado();
    }

    private function resolveEncabezado()
    {
        $default = ['-', '-', '-', '-', '-'];

        $puesto = Puesto::with(['socio.persona', 'block', 'gironegocio'])->find($this->id_puesto);

        if (!$puesto) {
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
        $deudas = Deuda::where('id_puesto', $this->id_puesto)
            ->get()
            ->map(function ($deuda) {
                $deudaCuotas = DeudaCuota::select('c.nombre')
                    ->join('cuota_servicios as b','deuda_cuotas.id_cuota_servicio','b.id_cuota_servicio')
                    ->join('servicios as c','b.id_servicio','c.id_servicio')
                    ->where('deuda_cuotas.id_deuda',$deuda->id_deuda)
                    ->groupBy('c.nombre')->get();
                $servicio_nombres = implode(', ', $deudaCuotas->pluck('nombre')->toArray());

                $importeSuma = DetallePagos::where('id_deuda',$deuda->id_deuda)->sum('importe');
                $importe_pagado = $importeSuma ?? 0;
                $importe_por_pagar = $deuda->total_deuda - $importe_pagado;

                return [
                    'fecha' => (new Carbon($deuda->fecha_registro))->format('Y-m-d'),
                    'servicio_descripcion' => $servicio_nombres,
                    'total' => $deuda->total_deuda,
                    'importe_pagado' => $importe_pagado ?? 0,
                    'importe_por_pagar' => $importe_por_pagar,
                ];
            });

        $this->count = count($deudas);
        return $deudas;
    }

    public function headings(): array
    {
        return [
            'Fec. Pago',
            'Servicios',
            'Total (S/.)',
            'Imp. Pagado (S/.)',
            'Imp. Por pagar (S/.)',
        ];
    }

    public function columnFormats(): array
    {
        return[
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle(1)->getFont()->setBold(true);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Inserta el bloque de cabecera (Nombre del socio, Bloque, Nro. Puesto, Area, Giro)
                // en las filas 1-2. La fila de encabezados pasa a la fila 3 y los datos a partir de la 4.
                $event->sheet->getDelegate()->insertNewRowBefore(1, 2);

                $labels = ['Nombre del socio', 'Bloque', 'Nro. Puesto', 'Area', 'Giro de negocio'];
                foreach ($labels as $i => $label) {
                    $column = chr(65 + $i);
                    $event->sheet->setCellValue($column . '1', $label);
                    $event->sheet->setCellValue($column . '2', $this->encabezado[$i]);
                }
                $event->sheet->getStyle('A1:E1')->getFont()->setBold(true);

                if ($this->count > 0) {
                    $lastRow = $event->sheet->getHighestRow() + 1;
                    $event->sheet->setCellValue('A' . ($lastRow), 'Total (S/.)');
                    $event->sheet->mergeCells("A{$lastRow}:B{$lastRow}");
                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:E{$lastRow}")->getFont()->setBold(true);
                    $event->sheet->setCellValue('C' . ($lastRow), '=SUM(C4:C' . ($lastRow - 1) . ')');
                    $event->sheet->setCellValue('D' . ($lastRow), '=SUM(D4:D' . ($lastRow - 1) . ')');
                    $event->sheet->setCellValue('E' . ($lastRow), '=SUM(E4:E' . ($lastRow - 1) . ')');
                }
            }
        ];
    }
}
