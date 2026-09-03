<?php

namespace App\Exports;

use App\Models\Socio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SociosExport implements FromCollection, WithHeadings, WithStyles
{
    private $rowCount = 1;

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = collect();

        Socio::with(['Persona', 'puestos.block', 'puestos.gironegocio', 'puestos.inquilino'])->orderByNombreCompleto()->get()->each(function ($socio) use ($data) {
            // Los datos personales viven en la tabla `personas` (relación 1:1 con el mismo ID)
            $persona = $socio->persona;

            $socioData = [
                'nombre' => trim($persona->nombre_completo ?? ($persona->nombre ?? '').' '.($persona->apellido_paterno ?? '').' '.($persona->apellido_materno ?? '')) ?: '------',
                'dni' => $persona->dni ?? '------',
                'telefono' => $persona->telefono ?? '------',
                'correo' => $persona->correo ?? '------',
                'fecha_registro' => $socio->fecha_registro ?? ($persona->fecha_registro ?? '------'),
            ];

            $rowStart = $this->rowCount + 1;

            $puestos = $socio->puestos->isEmpty() ? collect([(object) []]) : $socio->puestos;

            foreach ($puestos as $puesto) {
                $data->push([
                    $socioData['nombre'],
                    $socioData['dni'],
                    $socioData['telefono'],
                    $socioData['correo'],
                    data_get($puesto, 'block.nombre', '------'),
                    data_get($puesto, 'numero_puesto', '------'),
                    data_get($puesto, 'gironegocio.nombre', '------'),
                    trim(data_get($puesto, 'inquilino.nombre', '').' '.data_get($puesto, 'inquilino.apellido_paterno', '').' '.data_get($puesto, 'inquilino.apellido_materno', '')) ?: '------',
                    $socioData['fecha_registro'],
                ]);

                $socioData = array_fill_keys(array_keys($socioData), '');
            }

            $this->rowCount = $rowStart + max(count($puestos), 1) - 1;
        });

        return $data;
    }

    public function headings(): array
    {
        return [
            'Nombre Completo',
            'DNI',
            'Telefono',
            'Correo',
            'Block',
            'Puesto',
            'Giro Negocio',
            'Inquilino',
            'Fecha registro',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $row = 2;

        foreach (Socio::withCount('puestos')->orderByNombreCompleto()->get() as $socio) {
            $rowStart = $row;
            $rowEnd = $rowStart + max($socio->puestos_count, 1) - 1;

            if ($socio->puestos_count > 1) {

                $sheet->mergeCells("A{$rowStart}:A{$rowEnd}");
                $sheet->mergeCells("B{$rowStart}:B{$rowEnd}");
                $sheet->mergeCells("C{$rowStart}:C{$rowEnd}");
                $sheet->mergeCells("D{$rowStart}:D{$rowEnd}");
                $sheet->mergeCells("I{$rowStart}:I{$rowEnd}");

                $sheet->getStyle("A{$rowStart}:A{$rowEnd}")->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getStyle("B{$rowStart}:B{$rowEnd}")->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getStyle("C{$rowStart}:C{$rowEnd}")->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getStyle("D{$rowStart}:D{$rowEnd}")->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getStyle("I{$rowStart}:I{$rowEnd}")->getAlignment()->setHorizontal('center')->setVertical('center');
            }

            $row += $socio->puestos_count;
        }

        $sheet->getStyle(1)->getFont()->setBold(true);
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
}
