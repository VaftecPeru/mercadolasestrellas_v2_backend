<?php

namespace App\Exports\PDF;

use App\Models\Cuota;
use Barryvdh\DomPDF\PDF;

class CuotaPDFExport {

  public function generatePDF() {
  
    $cuotas = Cuota::with([
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

    $pdf = app(PDF::class)->loadView('exports.cuotas', ['cuotas' => $cuotas]);
    return $pdf->download('cuotas.pdf');

  }

}