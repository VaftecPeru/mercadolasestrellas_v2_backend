<?php

namespace App\Exports\PDF;

use App\Models\Socio;
use Barryvdh\DomPDF\PDF;

class SociosPDFExport {

  public function generatePDF() {

    $socios = Socio::with(['Usuario', 'Puestos.block', 'Puestos.gironegocio', 'Puestos.inquilino'])
      ->whereHas('Usuario', function ($query) {
        $query->where('estado', '0');
      })
      ->get()->map(function ($socio) {
      $puestos = $socio->Puestos->map(function ($puesto) {
      return [
          'block' => data_get($puesto, 'block.nombre', '------'),
          'giro' => data_get($puesto, 'gironegocio.nombre', '------'),
          'numero' => data_get($puesto, 'numero_puesto', '------'),
          'inquilino' => data_get($puesto, 'inquilino.nombre_completo', '------'),
        ];
      });

      if ($puestos->isEmpty()) {
        $puestos->push([
          'block' => '------',
          'giro' => '------',
          'numero' => '------',
          'inquilino' => '------',
        ]);
      }

      return [
        'nombre' => trim(($socio->nombres ?? '').' '.($socio->apellido_paterno ?? '').' '.($socio->apellido_materno ?? '')) ?: '------', 
        'dni' => $socio->dni ?? '------', 
        'telefono' => $socio->telefono ?? '------', 
        'correo' => $socio->correo ?? '------', 
        'puestos' => $puestos,
        'fecha_registro' => $socio->fecha_registro ?? '------',
      ];
    });

    $pdf = app(PDF::class)->loadView('exports.socios', ['socios' => $socios]);
    return $pdf->download('socios.pdf');

  }

}