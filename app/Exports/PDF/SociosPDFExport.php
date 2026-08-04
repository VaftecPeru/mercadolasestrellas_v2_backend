<?php

namespace App\Exports\PDF;

use App\Models\Socio;
use Barryvdh\DomPDF\PDF;

class SociosPDFExport {

  public function generatePDF() {

    $socios = Socio::with(['Persona', 'Puestos.block', 'Puestos.gironegocio', 'Puestos.inquilino'])
      ->where('socios.estado', '1')
      ->get()->map(function ($socio) {
      $persona = $socio->persona;
      $puestos = $socio->Puestos->map(function ($puesto) {
      return [
          'block' => data_get($puesto, 'block.nombre', '------'),
          'giro' => data_get($puesto, 'gironegocio.nombre', '------'),
          'numero' => data_get($puesto, 'numero_puesto', '------'),
          'inquilino' => trim(data_get($puesto, 'inquilino.nombre', '').' '.data_get($puesto, 'inquilino.apellido_paterno', '').' '.data_get($puesto, 'inquilino.apellido_materno', '')) ?: '------',
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
        'nombre' => trim($persona->nombre_completo ?? ($persona->nombre ?? '').' '.($persona->apellido_paterno ?? '').' '.($persona->apellido_materno ?? '')) ?: '------',
        'dni' => $persona->dni ?? '------', 
        'telefono' => $persona->telefono ?? '------', 
        'correo' => $persona->correo ?? '------', 
        'puestos' => $puestos,
        'fecha_registro' => $socio->fecha_registro ?? ($persona->fecha_registro ?? '------'),
      ];
    });

    $pdf = app(PDF::class)->loadView('exports.socios', ['socios' => $socios]);
    return $pdf->download('socios.pdf');

  }

}