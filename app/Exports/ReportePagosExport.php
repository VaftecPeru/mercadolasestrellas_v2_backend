<?php

namespace App\Exports;

use App\Models\Pago;
use App\Models\Puesto;
use App\Models\Socio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportePagosExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithStrictNullComparison
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

        if (request()->has('id_puesto') && request()->id_puesto != "") {
            $puesto = Puesto::with(['socio.persona', 'block', 'gironegocio'])->find($this->filtro_id);

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

        $socio = Socio::with('persona')->find($this->filtro_id);

        if (!$socio || !$socio->persona) {
            return $default;
        }

        return [
            trim(($socio->persona->nombre ?? '') . ' ' . ($socio->persona->apellido_paterno ?? '') . ' ' . ($socio->persona->apellido_materno ?? '')) ?: 'Socio',
            '-',
            '-',
            '-',
            '-',
        ];
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
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Setiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
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
            'Monto (S/.)',
            'Imp. Pagado (S/.)',
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
                    $event->sheet->mergeCells("A{$lastRow}:E{$lastRow}");                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:F{$lastRow}")->getFont()->setBold(true);
                    
                    // Sumatoria solo de la columna F (Pago S/). Los datos inician en la fila 4.
                    $event->sheet->setCellValue('F' . ($lastRow), '=SUM(F4:F' . ($lastRow - 1) . ')');
                }
            }
        ];
    }
}
