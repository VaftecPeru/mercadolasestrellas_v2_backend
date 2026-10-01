<?php

namespace App\Exports;

use App\Models\Puesto;
use App\Support\Comprobante;
use App\Support\ReporteResumen;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteResumenExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithStrictNullComparison, WithStyles
{
    protected $filtro_id;

    protected $encabezado = ['-', '-', '-', '-', '-'];

    private $count = 0;

    public function __construct($filtro_id)
    {
        $this->filtro_id = $filtro_id;
        $this->encabezado = $this->resolveEncabezado();
    }

    private function resolveEncabezado()
    {
        $default = ['-', '-', '-', '-', '-'];

        $puesto = Puesto::with(['socio.persona', 'block', 'gironegocio'])->find($this->filtro_id);

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
        $detalles = ReporteResumen::query($this->filtro_id)
            ->get()
            ->map(function ($row) {
                return [
                    'serie_numero' => Comprobante::formatear($row->serie, $row->numero_pago),
                    'importe_ingreso' => $row->importe_ingreso,
                    'importe_gastos_administrativo' => $row->importe_gastos_administrativo,
                    'importe_otros_servicios' => $row->importe_otros_servicios,
                    'importe_multas_inasistencia' => $row->importe_multas_inasistencia,
                    'importe_pagos_banco' => $row->importe_pagos_banco,
                    'importe_pagos_efectivo' => $row->importe_pagos_efectivo,
                    'importe_cuotas_extraordinarias' => $row->importe_cuotas_extraordinarias,
                ];
            });

        $this->count = count($detalles);

        return $detalles;
    }

    public function headings(): array
    {
        return [
            'N° Recibo',
            'Imp. Ingreso',
            'Imp. Gastos Administrativo',
            'Imp. Otros Servicios',
            'Imp. Multas Inasistencia',
            'Imp. Banco',
            'Imp. Efectivo',
            'Imp. Cuotas Extraordinarias',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_00,
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle(1)->getFont()->setBold(true);

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

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
                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:H{$lastRow}")->getFont()->setBold(true);
                    $event->sheet->setCellValue('B'.($lastRow), '=SUM(B4:B'.($lastRow - 1).')');
                    $event->sheet->setCellValue('C'.($lastRow), '=SUM(C4:C'.($lastRow - 1).')');
                    $event->sheet->setCellValue('D'.($lastRow), '=SUM(D4:D'.($lastRow - 1).')');
                    $event->sheet->setCellValue('E'.($lastRow), '=SUM(E4:E'.($lastRow - 1).')');
                    $event->sheet->setCellValue('F'.($lastRow), '=SUM(F4:F'.($lastRow - 1).')');
                    $event->sheet->setCellValue('G'.($lastRow), '=SUM(G4:G'.($lastRow - 1).')');
                    $event->sheet->setCellValue('H'.($lastRow), '=SUM(H4:H'.($lastRow - 1).')');
                }
            },
        ];
    }
}
