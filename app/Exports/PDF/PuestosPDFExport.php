<?php

namespace App\Exports\PDF;

use App\Models\Puesto;
use Barryvdh\DomPDF\PDF;

class PuestosPDFExport {

  public function generatePDF() {

    $puestos = Puesto::with([
      'socio.persona',
      'socio.usuario',
      'block',
      'gironegocio',
      'inquilino' 
    ])->where('activo', true)->get()->map(function($puesto) {
      return [
        'bloque' => data_get($puesto, 'block.nombre', '------'), 
        'puesto' => $puesto->numero_puesto ?? '------', 
        'area' => $puesto->area ?? '------', 
        'giro' => data_get($puesto, 'gironegocio.nombre', '------'), 
        // el nombre del socio desde la tabla personas
        'socio' => data_get($puesto, 'socio.persona.nombre_completo', '------') ?: data_get($puesto, 'socio.usuario.nombre_usuario', '------'),
        'inquilino' => trim(data_get($puesto, 'inquilino.nombre', '').' '.data_get($puesto, 'inquilino.apellido_paterno', '').' '.data_get($puesto, 'inquilino.apellido_materno', '')) ?: '------', 
        'estado' => $puesto->estado === '1' ?  'Libre' : 'Ocupado',
        'fecha_registro' => $puesto->fecha_registro ?? '------', 
      ];
    });

    $pdf = app(PDF::class)->loadView('exports.puestos', ['puestos' => $puestos]);
    return $pdf->download('puestos.pdf');

  }

}