<?php

namespace App\Exports;

use App\Models\Cuota;
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

class ReporteCuotasMetradoExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithStrictNullComparison
{
    protected $filtro_id;
    protected $encabezado = ['-', '-'];
    private $count = 0;

    public function __construct($filtro_id)
    {
        $this->filtro_id = $filtro_id;
        $this->encabezado = $this->resolveEncabezado();
    }

    private function resolveEncabezado()
    {
        $default = ['-', '-'];

        $cuota = Cuota::find($this->filtro_id);

        if (!$cuota) {
            return $default;
        }

        return [
            $cuota->fecha_emision ?? '-',
            $cuota->fecha_vencimiento ?? '-',
        ];
    }

    public function collection()
    {
        $id_cuota = $this->filtro_id;
        $deudas = Deuda::whereExists(function ($query) use ($id_cuota) {
                $query->select("deuda_cuotas.id_deuda")
                    ->from('deuda_cuotas')
                    ->join('cuota_servicios','deuda_cuotas.id_cuota_servicio','cuota_servicios.id_cuota_servicio')
                    ->whereRaw('deudas.id_deuda = deuda_cuotas.id_deuda')
                    ->where('cuota_servicios.id_cuota', $id_cuota);
            })
            ->get()
            ->map(function ($deuda) use ($id_cuota) {

                $importeSuma = DetallePagos::where('id_deuda',$deuda->id_deuda)->sum('importe');
                $importe_pagado = $importeSuma ? $importeSuma : 0;

                return [
                    'id_cuota' => $id_cuota,
                    'fecha' => $deuda->fecha_registro,
                    'nombre_completo' => $deuda->socio && $deuda->socio->persona ? $deuda->socio->persona->nombre_completo : '',
                    'numero_puesto' => $deuda->puesto ? $deuda->puesto->numero_puesto : '',
                    'area' => $deuda->puesto ? $deuda->puesto->area : '',
                    'total' => $deuda->total_deuda,
                    'importe_pagado' => $importe_pagado,
                    'importe_por_pagar' => $deuda->total_deuda - $importe_pagado,
                ];

            });

        $this->count = count($deudas);
        return $deudas;
    }

    public function headings(): array
    {
        return [
            'ID Cuota',
            'Fec. Registro',
            'Nombre del socio',
            'N° Puesto',
            'Área (m2)',
            'Total (S/)',
            'Imp. Pagado (S/.)',
            'Imp. Por pagar (S/)'
        ];
    }

    public function columnFormats(): array
    {
        return[
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00
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
            AfterSheet::class => function(AfterSheet $event) {
                // Inserta el bloque de cabecera (Fecha de emisión, Fecha de vencimiento)
                // en las filas 1-2. La fila de encabezados pasa a la fila 3 y los datos a partir de la 4.
                $event->sheet->getDelegate()->insertNewRowBefore(1, 2);

                $labels = ['Fecha de emisión', 'Fecha de vencimiento'];
                foreach ($labels as $i => $label) {
                    $column = chr(65 + $i);
                    $event->sheet->setCellValue($column . '1', $label);
                    $event->sheet->setCellValue($column . '2', $this->encabezado[$i]);
                }
                $event->sheet->getStyle('A1:B1')->getFont()->setBold(true);

                if ($this->count > 0) {
                    $lastRow = $event->sheet->getHighestRow() + 1;
                    $event->sheet->setCellValue('A' . ($lastRow), 'Total (S/.)');
                    $event->sheet->mergeCells("A{$lastRow}:D{$lastRow}");
                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:H{$lastRow}")->getFont()->setBold(true);
                    $event->sheet->setCellValue('F' . ($lastRow), '=SUM(F4:F' . ($lastRow - 1) . ')');
                    $event->sheet->setCellValue('G' . ($lastRow), '=SUM(G4:G' . ($lastRow - 1) . ')');
                    $event->sheet->setCellValue('H' . ($lastRow), '=SUM(H4:H' . ($lastRow - 1) . ')');
                }
            }
        ];
    }
}
