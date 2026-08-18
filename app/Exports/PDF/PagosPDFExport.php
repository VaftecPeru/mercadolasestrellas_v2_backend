<?php

namespace App\Exports\PDF;

use App\Models\Pago;
use App\Models\DetallePagos;
use App\Models\Deuda;
use Barryvdh\DomPDF\PDF;

class PagosPDFExport {

  public function generatePDF() {

    $pagos = Pago::with([
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
          'numero_puesto' => data_get($pago, 'Socio.Puestos.0.numero_puesto', '------'), 
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

    $pdf = app(PDF::class)->loadView('exports.pagos', ['pagos' => $pagos]);
    return $pdf->download('pagos.pdf');

  }

}