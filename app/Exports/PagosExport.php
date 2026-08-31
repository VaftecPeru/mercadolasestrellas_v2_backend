<?php

namespace App\Exports;

use App\Models\Pago;
use App\Models\DetallePagos;
use App\Models\Deuda;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PagosExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithStrictNullComparison
{
    private $count = 0;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $rows = Pago::with([
            'Socio.Puestos',
            'Socio.Usuario',
            'Socio.Persona',
            'DetallePagos',
        ])->get()->map(function($pago) {
            $a_cuenta = '------';

            if ($pago->DetallePagos && $pago->DetallePagos->isNotEmpty()) {
                $a_cuenta = $pago->DetallePagos->sum('importe');
            }

            // Calcular deuda restante igual que en PagoCollection
            $idsPuesto = $pago->DetallePagos->pluck('id_puesto')->unique()->toArray();
            
            $importePago = DetallePagos::select('importe')
                ->whereIn('id_puesto', $idsPuesto)
                ->sum('importe');
            $importeDeuda = Deuda::select('total_deuda')
                ->whereIn('id_puesto', $idsPuesto)
                ->sum('total_deuda');
            $monto_actual = ($importeDeuda ?? 0) - ($importePago ?? 0);

            return [
                'id' => $pago->id_pago ?? '------', 
                'numero_puesto' => data_get($pago, 'Socio.Puestos.0.numero_puesto', '------'), 
                // Obtener nombre del socio desde la tabla personas 
                'socio' => data_get($pago, 'Socio.Persona.nombre_completo', '------') ?: data_get($pago, 'Socio.Usuario.nombre_usuario', '------'),
                'dni' => data_get($pago, 'Socio.Persona.dni', '------'), 
                'fecha_registro' => $pago->fecha_registro ?? '------', 
                'telefono' => data_get($pago, 'Socio.Persona.telefono', '------'), 
                'correo' => data_get($pago, 'Socio.Persona.correo', '------'), 
                'a_cuenta' => $a_cuenta,
                'monto_actual' => number_format($monto_actual, 2, '.', ''), 
            ];
        });

        $this->count = count($rows);
        return $rows;
    }
    
    
    public function headings(): array
    {
        return [
            'ID',
            'Nro. Puesto',
            'Socio',
            'DNI',
            'Fec. Pago',
            'Telefono',
            'Correo',
            'A cuenta',
            'Monto Actual',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        
        $sheet->getStyle(1)->getFont()->setBold(true);

       
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                if ($this->count > 0) {
                    $lastRow = $event->sheet->getHighestRow() + 1;
                    $event->sheet->setCellValue('A' . $lastRow, 'Total (S/.)');
                    $event->sheet->mergeCells("A{$lastRow}:H{$lastRow}");
                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:I{$lastRow}")->getFont()->setBold(true);
                    $event->sheet->getStyle("I{$lastRow}")->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('e3f2fd');
                    // SUM de la columna I (Monto Actual), datos desde fila 2
                    $event->sheet->setCellValue('I' . $lastRow, '=SUM(I2:I' . ($lastRow - 1) . ')');
                }
            }
        ];
    }

}
