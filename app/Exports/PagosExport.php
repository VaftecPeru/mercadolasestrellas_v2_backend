<?php

namespace App\Exports;

use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\Pago;
use App\Support\Comprobante;
use App\Support\FiltroTexto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PagosExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithStrictNullComparison, WithStyles
{
    protected $id_puesto;

    protected $search;

    protected $id_socio;

    private $count = 0;

    public function __construct($id_puesto = null, $search = null, $id_socio = null)
    {
        $this->id_puesto = $id_puesto ?: request()->input('id_puesto');
        $this->search = $search ?: (request()->input('search') ?: request()->input('buscar_texto'));
        $this->id_socio = $id_socio ?: request()->input('id_socio');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Pago::with([
            'Socio.Puestos',
            'Socio.Usuario',
            'Socio.Persona',
            'DetallePagos.puesto',
        ])->orderBy('fecha_registro', 'desc');

        if ($this->id_puesto) {
            $query->whereHas('DetallePagos', function ($q) {
                $q->where('id_puesto', $this->id_puesto);
            });
        }

        if ($this->id_socio) {
            $query->where('pagos.id_socio', $this->id_socio);
        }

        if ($this->search) {
            $texto = FiltroTexto::normalizarNombre($this->search);
            $query->where(function ($q) use ($texto) {
                $q->whereHas('socio.persona', function ($sq) use ($texto) {
                    $sq->whereRaw('upper(nombre_completo) LIKE upper(?)', ['%'.$texto.'%'])
                        ->orWhere('dni', 'LIKE', '%'.$texto.'%');
                })->orWhereHas('socio.usuario', function ($sq) use ($texto) {
                    $sq->whereRaw('upper(nombre_usuario) LIKE upper(?)', ['%'.$texto.'%']);
                });
            });
        }

        $rows = $query->get()->map(function ($pago) {
            $a_cuenta = '------';

            if ($pago->DetallePagos && $pago->DetallePagos->isNotEmpty()) {
                $a_cuenta = $pago->DetallePagos->sum('importe');
            }

            // Puestos involucrados en este pago
            $puestosPago = $pago->DetallePagos->pluck('puesto.numero_puesto')->filter()->unique()->toArray();
            $numeroPuesto = ! empty($puestosPago)
                ? implode(', ', $puestosPago)
                : data_get($pago, 'Socio.Puestos.0.numero_puesto', '------');

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
                'id' => Comprobante::formatear($pago->serie, $pago->numero_pago),
                'numero_puesto' => $numeroPuesto,
                // Obtener nombre del socio desde la tabla personas
                'socio' => data_get($pago, 'Socio.Persona.nombre_completo', '------') ?: data_get($pago, 'Socio.Usuario.nombre_usuario', '------'),
                'dni' => data_get($pago, 'Socio.Persona.dni', '------'),
                'fecha_registro' => $pago->fecha_registro ?? '------',
                'telefono' => data_get($pago, 'Socio.Persona.telefono', '------'),
                'correo' => data_get($pago, 'Socio.Persona.correo', '------'),
                'a_cuenta' => is_numeric($a_cuenta) ? number_format((float) $a_cuenta, 2, '.', '') : $a_cuenta,
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
            'Fecha Pago',
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
            AfterSheet::class => function (AfterSheet $event) {
                if ($this->count > 0) {
                    $lastRow = $event->sheet->getHighestRow() + 1;
                    $event->sheet->setCellValue('A'.$lastRow, 'Total (S/.)');
                    $event->sheet->mergeCells("A{$lastRow}:G{$lastRow}");
                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:I{$lastRow}")->getFont()->setBold(true);
                    $event->sheet->getStyle("H{$lastRow}:I{$lastRow}")->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('e3f2fd');
                    // SUM de las columnas H (A cuenta) e I (Monto Actual), datos desde fila 2
                    $event->sheet->setCellValue('H'.$lastRow, '=SUM(H2:H'.($lastRow - 1).')');
                    $event->sheet->setCellValue('I'.$lastRow, '=SUM(I2:I'.($lastRow - 1).')');
                }
            },
        ];
    }
}
