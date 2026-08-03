<?php

namespace App\Exports;

use App\Models\Puesto;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PuestosExport implements FromCollection, WithHeadings, WithStyles
{    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        return Puesto::with([
            'socio.usuario',
            'block',
            'gironegocio',
            'inquilino' 
        ])->where('activo', true)->get()->map(function($puesto) {
            return [
                'bloque' => data_get($puesto, 'block.nombre', '------'), 
                'puesto' => $puesto->numero_puesto ?? '------', 
                'area' => $puesto->area ?? '------', 
                'giro' => data_get($puesto, 'gironegocio.nombre', '------'), 
                'socio' => data_get($puesto, 'socio.usuario.nombre_usuario', '------'), 
                'inquilino' => data_get($puesto, 'inquilino.nombre_completo', '------'), 
                'estado' => $puesto->estado === '1' ?  'Libre' : 'Ocupado',
                'fecha_registro' => $puesto->fecha_registro ?? '------',
            ];
        });
    }
    
    public function headings(): array
    {
        return [
            'Bloque',
            'Puesto',
            'Area',
            'Giro Negocio',
            'Socio',
            'Inquilino',
            'Estado',
            'Fecha registro',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle(1)->getFont()->setBold(true);

        
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

}

