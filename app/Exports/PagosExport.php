<?php

namespace App\Exports;

use App\Models\Pago;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PagosExport implements  FromCollection, WithHeadings, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Pago::with([
            'Socio.Puestos',
            'Socio.Usuario',
            'Socio.Persona',
            'DetallePagos',
        ])->get()->map(function($pago) {
            $a_cuenta = '------';

            if ($pago->DetallePagos && $pago->DetallePagos->isNotEmpty()) {
                $a_cuenta = $pago->DetallePagos->sum('importe');
            }

            return [
                'id' => $pago->id_pago?? '------', 
                'n_puesto' => data_get($pago, 'Socio.Puestos.0.numero_puesto', '------'), 
                'socio' => data_get($pago, 'Socio.Usuario.nombre_usuario', '------'), 
                'dni' => data_get($pago, 'Socio.Persona.dni', '------'), 
                'fecha_registro' => $pago->fecha_registro ?? '------', 
                'telefono' => data_get($pago, 'Socio.Persona.telefono', '------'), 
                'correo' => data_get($pago, 'Socio.Persona.correo', '------'), 
                'a_cuenta' => $a_cuenta,
                'monto_total' => $pago->total_pago ?? '------', 
            ];
        });
    }
    
    
    public function headings(): array
    {
        return [
            'ID',
            'Nro. Puesto',
            'Socio',
            'DNI',
            'fecha_registro',
            'Telefono',
            'Correo',
            'A cuenta',
            'Monto Actual',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        
        $sheet->getStyle(1)->getFont()->setBold(true);

       
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

}
