<?php

namespace App\Exports;

use App\Models\Pago;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportePagosExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithColumnFormatting
{
    
    protected $filtro_id;
    private $count = 0;

    public function __construct($filtro_id)
    {
        $this->filtro_id = $filtro_id;
    }

    public function collection()
    {
        $query = Pago::query();

        if (request()->has('id_puesto') && request()->id_puesto != "") {
            $query->whereHas('DetallePagos', function($q) {
                $q->where('id_puesto', $this->filtro_id);
            });
        } else {
            $query->where('id_socio', $this->filtro_id);
        }

        $pagosMap = $query->with(['DetallePagos.servicio']) 
            ->get();

        $rows = [];
        $meses = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
        ];

        foreach ($pagosMap as $pago) {
            $fecha = \Carbon\Carbon::parse($pago->fecha_registro);
            $anio = $fecha->year;
            $mes = $meses[$fecha->month];
            $fechaFmt = $fecha->format('Y-m-d');
            
            $detalles = $pago->DetallePagos;
            $countDetalles = count($detalles);

            foreach ($detalles as $index => $detalle) {
                $rows[] = [
                    'anio'      => $anio,
                    'mes'       => $mes,
                    'fecha'     => $fechaFmt,
                    'servicio'  => $detalle->servicio->nombre ?? 'Servicio',
                    'monto'     => $detalle->importe,
                    'total'     => ($index === $countDetalles - 1) ? $pago->total_pago : null,
                ];
            }
        }

        $this->count = count($rows);
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Año',
            'Mes',
            'Fec. Pago',
            'Servicios',
            'Monto (S/)',
            'Pago (S/)',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00
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
            AfterSheet::class => function(AfterSheet $event) {
                if ($this->count > 0) {
                    $lastRow = $event->sheet->getHighestRow() + 1;
                    $event->sheet->setCellValue('A' . ($lastRow), 'TOTAL GENERAL:');
                    $event->sheet->mergeCells("A{$lastRow}:E{$lastRow}");
                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:F{$lastRow}")->getFont()->setBold(true);
                    
                    // Sumatoria solo de la columna F (Pago S/)
                    $event->sheet->setCellValue('F' . ($lastRow), '=SUM(F2:F' . ($lastRow - 1) . ')');
                }
            }
        ];
    }
}
