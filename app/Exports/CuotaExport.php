<?php

namespace App\Exports;

use App\Models\Cuota;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CuotaExport implements FromCollection, WithHeadings, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
        return Cuota::with([
            'puestosCuota.puesto',
            'cuotaServicios.servicio'
        ])->get()->map(function($cuota) {
            
            // Verificar si es cuota global
            $puestosAsignados = ($cuota->global == '1' || $cuota->global === 1 || $cuota->global === true)
                ? 'Todos'
                : ($cuota->puestosCuota
                    ->pluck('puesto.numero_puesto')
                    ->filter()
                    ->implode(', ') ?: 'Ninguno');
            
            // Obtener nombres de servicios
            $servicios = $cuota->cuotaServicios
                ->pluck('servicio.nombre')
                ->filter()
                ->implode(', ') ?: 'Ninguno';
            
            return [
                'id' => $cuota->id_cuota ?? '------', 
                'fecha_emision' => $cuota->fecha_emision ?? '------', 
                'fecha_vencimiento' => $cuota->fecha_vencimiento ?? '------', 
                'importe' => $cuota->importe ?? '------',
                'puestos' => $puestosAsignados,
                'servicios' => $servicios,
            ];
        });
    }
    
    public function headings(): array
    {
        return [
            'ID',
            'Fec. Emisión',
            'Fec. Vencimiento',
            'Importe',
            'Puestos Asignados',
            'Servicios',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        
        $sheet->getStyle(1)->getFont()->setBold(true);

        
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

}
