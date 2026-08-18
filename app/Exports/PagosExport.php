<?php

namespace App\Exports;

use App\Models\Pago;
use App\Models\DetallePagos;
use App\Models\Deuda;
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
                'n_puesto' => data_get($pago, 'Socio.Puestos.0.numero_puesto', '------'), 
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
