<?php

namespace App\Exports\PDF;

use App\Models\Pago;
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

      return [
          'id' => $pago->id_pago?? '------', 
          'numero_puesto' => data_get($pago, 'Socio.Puestos.0.numero_puesto', '------'), 
          // Obtener nombre del socio desde la tabla personas 
          'socio' => data_get($pago, 'Socio.Persona.nombre_completo', '------') ?: data_get($pago, 'Socio.Usuario.nombre_usuario', '------'),
          'dni' => data_get($pago, 'Socio.Persona.dni', '------'), 
          'fecha_registro' => $pago->fecha_registro ?? '------', 
          'telefono' => data_get($pago, 'Socio.Persona.telefono', '------'), 
          'correo' => data_get($pago, 'Socio.Persona.correo', '------'), 
          'a_cuenta' => $a_cuenta,
          'monto_total' => $pago->total_pago ?? '------', 
      ];
    });

    $pdf = app(PDF::class)->loadView('exports.pagos', ['pagos' => $pagos]);
    return $pdf->download('pagos.pdf');

  }

}