<?php

namespace App\Exports;

use App\Models\Pago;
use App\Models\Puesto;
use App\Models\Socio;
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

class ReportePagosExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithStrictNullComparison, WithStyles
{
    protected $filtro_id;

    protected $encabezado = ['-', '-', '-', '-', '-'];

    private $count = 0;

    // true = columnas de la pestaña "Pagos Realizados": Fec. Pago | Comprobante | Concepto | Monto
    private $modoDetalle = false;

    public function __construct($filtro_id)
    {
        $this->filtro_id = $filtro_id;
        $this->modoDetalle = request()->query('modo') === 'detalle';
        $this->encabezado = $this->resolveEncabezado();
    }

    private function resolveEncabezado()
    {
        $default = ['-', '-', '-', '-', '-'];

        if (request()->has('id_puesto') && request()->id_puesto != '') {
            $puesto = Puesto::with(['socio.persona', 'block', 'gironegocio'])->find($this->filtro_id);

            if (! $puesto) {
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

        if (! $socio || ! $socio->persona) {
            return $default;
        }

        return [
            trim(($socio->persona->nombre ?? '').' '.($socio->persona->apellido_paterno ?? '').' '.($socio->persona->apellido_materno ?? '')) ?: 'Socio',
            '-',
            '-',
            '-',
            '-',
        ];
    }

    public function collection()
    {
        $query = Pago::query();

        if (request()->has('id_puesto') && request()->id_puesto != '') {
            $query->whereHas('DetallePagos', function ($q) {
                $q->where('id_puesto', $this->filtro_id);
            });
        } else {
            $query->where('id_socio', $this->filtro_id);
        }

        if (request()->has('nombre_socio') && request()->nombre_socio != '') {
            $texto = FiltroTexto::normalizarNombre(request()->nombre_socio);
            $query->whereHas('socio.persona', function ($q) use ($texto) {
                $q->whereRaw('upper(nombre_completo) LIKE upper(?)', ['%'.$texto.'%']);
            });
        }

        $pagosMap = $query->with(['DetallePagos.servicio'])
            ->get();

        $rows = [];

        foreach ($pagosMap as $pago) {
            $fecha = \Carbon\Carbon::parse($pago->fecha_registro);
            $fechaFmt = $fecha->format('Y-m-d');

            $detalles = $pago->DetallePagos;
            $countDetalles = count($detalles);

            foreach ($detalles as $index => $detalle) {
                if ($this->modoDetalle) {
                    $rows[] = [
                        'fecha' => $index === 0 ? $fechaFmt : '',
                        'comprobante' => $index === 0 ? Comprobante::formatear($pago->serie, $pago->numero_pago) : '',
                        'concepto' => $detalle->servicio->nombre ?? 'Servicio',
                        'monto' => ($index === $countDetalles - 1) ? $pago->total_pago : $detalle->importe,
                    ];
                } else {
                    $rows[] = [
                        'fecha' => $fechaFmt,
                        'servicio' => $detalle->servicio->nombre ?? 'Servicio',
                        'monto' => $detalle->importe,
                        'total' => ($index === $countDetalles - 1) ? $pago->total_pago : null,
                    ];
                }
            }
        }

        $this->count = count($rows);

        return collect($rows);
    }

    public function headings(): array
    {
        if ($this->modoDetalle) {
            return [
                'Fec. Pago',
                'Comprobante',
                'Concepto',
                'Monto (S/.)',
            ];
        }

        return [
            'Fec. Pago',
            'Servicios',
            'Total (S/.)',
            'Imp. Pagado (S/.)',
        ];
    }

    public function columnFormats(): array
    {
        if ($this->modoDetalle) {
            return [
                'D' => NumberFormat::FORMAT_NUMBER_00,
            ];
        }

        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {

        $sheet->getStyle(1)->getFont()->setBold(true);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Inserta el bloque de cabecera (Nombre del socio, Bloque, Nro. Puesto, Area, Giro)
                // en las filas 1-2. La fila de encabezados pasa a la fila 3 y los datos a partir de la 4.
                $event->sheet->getDelegate()->insertNewRowBefore(1, 2);

                $labels = ['Nombre del socio', 'Bloque', 'Nro. Puesto', 'Area', 'Giro de negocio'];
                foreach ($labels as $i => $label) {
                    $column = chr(65 + $i);
                    $event->sheet->setCellValue($column.'1', $label);
                    $event->sheet->setCellValue($column.'2', $this->encabezado[$i]);
                }
                $event->sheet->getStyle('A1:E1')->getFont()->setBold(true);

                if ($this->count > 0) {
                    $lastRow = $event->sheet->getHighestRow() + 1;
                    $event->sheet->setCellValue('A'.($lastRow), 'Total (S/.)');
                    $event->sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal('right');
                    $event->sheet->getStyle("A{$lastRow}:D{$lastRow}")->getFont()->setBold(true);

                    if ($this->modoDetalle) {
                        $event->sheet->mergeCells("A{$lastRow}:C{$lastRow}");
                        $event->sheet->setCellValue('D'.($lastRow), '=SUM(D4:D'.($lastRow - 1).')');
                    } else {
                        $event->sheet->mergeCells("A{$lastRow}:B{$lastRow}");
                        $event->sheet->setCellValue('C'.($lastRow), '=SUM(C4:C'.($lastRow - 1).')');
                        $event->sheet->setCellValue('D'.($lastRow), '=SUM(D4:D'.($lastRow - 1).')');
                    }
                }
            },
        ];
    }
}
