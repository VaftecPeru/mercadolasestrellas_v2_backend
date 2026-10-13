<?php

namespace App\Exports;

use App\Models\Servicio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServicioExport implements FromCollection, WithColumnFormatting, WithHeadings, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Servicio::where('activo', true)->get()->map(function ($servicio) {
            return [
                'id' => $servicio->id_servicio ?? '------',
                'nombre' => $servicio->nombre ?? '------',
                'costo_unitario' => $servicio->costo_unitario ?? '------',
                'tipo_servicio' => Servicio::nombreTipoServicio($servicio->tipo_servicio),
                'fecha_registro' => $servicio->fecha_registro ? \Carbon\Carbon::parse($servicio->fecha_registro)->format('Y-m-d') : '------',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre del servicio',
            'Costo Unitario',
            'Tipo de Servicio',
            'Fecha Registro',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {

        $sheet->getStyle(1)->getFont()->setBold(true);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
}
