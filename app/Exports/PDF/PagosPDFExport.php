<?php

namespace App\Exports\PDF;

use App\Models\DetallePagos;
use App\Models\Deuda;
use App\Models\Pago;
use App\Support\Comprobante;
use App\Support\FiltroTexto;
use Barryvdh\DomPDF\PDF;

class PagosPDFExport
{
    public function generatePDF($id_puesto = null, $search = null, $id_socio = null)
    {
        $id_puesto = $id_puesto ?: request()->input('id_puesto');
        $search = $search ?: (request()->input('search') ?: request()->input('buscar_texto'));
        $id_socio = $id_socio ?: request()->input('id_socio');

        $query = Pago::with([
            'Socio.Puestos',
            'Socio.Usuario',
            'Socio.Persona',
            'DetallePagos.puesto',
        ])->orderBy('fecha_registro', 'desc');

        if ($id_puesto) {
            $query->whereHas('DetallePagos', function ($q) use ($id_puesto) {
                $q->where('id_puesto', $id_puesto);
            });
        }

        if ($id_socio) {
            $query->where('pagos.id_socio', $id_socio);
        }

        if ($search) {
            $texto = FiltroTexto::normalizarNombre($search);
            $query->where(function ($q) use ($texto) {
                $q->whereHas('socio.persona', function ($sq) use ($texto) {
                    $sq->whereRaw('upper(nombre_completo) LIKE upper(?)', ['%'.$texto.'%'])
                        ->orWhere('dni', 'LIKE', '%'.$texto.'%');
                })->orWhereHas('socio.usuario', function ($sq) use ($texto) {
                    $sq->whereRaw('upper(nombre_usuario) LIKE upper(?)', ['%'.$texto.'%']);
                });
            });
        }

        $pagos = $query->get()->map(function ($pago) {
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

        $total = $pagos->sum(fn ($p) => (float) $p['monto_actual']);
        $total_a_cuenta = $pagos->sum(fn ($p) => (float) $p['a_cuenta']);

        $pdf = app(PDF::class)->loadView('exports.pagos', [
            'pagos' => $pagos,
            'total' => $total,
            'total_a_cuenta' => $total_a_cuenta,
        ]);

        return $pdf->download('pagos.pdf');
    }
}
