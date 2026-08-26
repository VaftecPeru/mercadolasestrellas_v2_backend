<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exportar Reporte de Cuotas por Metrado</title>
  <style>
    *{
      font-family: Arial, Helvetica, sans-serif;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th,
    td {
      font-size: 12px;
      border: 1px solid #dee2e6;
      padding: 8px;
    }

    th {
      background-color: #f8f9fa;
      font-weight: bold;
      text-align: center;
    }

    h2 {
      text-align: center;
      margin-top: 20px;
    }

    .right { text-align: right; }
  </style>
</head>
<body>
  <h2>MERCADO LAS ESTRELLAS - REPORTE DE CUOTAS POR METRADO</h2>
  <table>
    <thead>
      <tr>
        <th>Fecha de emisión</th>
        <th>Fecha de vencimiento</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $fecha_emision }}</td>
        <td>{{ $fecha_vencimiento }}</td>
      </tr>
    </tbody>
  </table>
  <table>
    <thead>
      <tr>
        <th>ID Cuota</th>
        <th>Nombre Completo</th>
        <th>Número de Puesto</th>
        <th>Área</th>
        <th>Total (S/.)</th>
        <th>Imp. Pagado (S/.)</th>
        <th>Imp. Por pagar (S/.)</th>
        <th>Fecha de Registro</th>
      </tr>
    </thead>
    <tbody>
      @foreach($deudas as $deuda)
        <tr>
          <td>{{ $deuda['id_cuota'] }}</td>
          <td>{{ $deuda['nombre_completo'] }}</td>
          <td>{{ $deuda['numero_puesto'] }}</td>
          <td class="right">{{ $deuda['area'] }}</td>
          <td class="right">S/ {{ number_format($deuda['total'], 2) }}</td>
          <td class="right">S/ {{ number_format($deuda['importe_pagado'], 2) }}</td>
          <td class="right">S/ {{ number_format($deuda['total'] - $deuda['importe_pagado'], 2) }}</td>
          <td>{{ $deuda['fecha_registro'] }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th colspan="5" class="right">Total (S/.)</th>
        <th class="right" style="background-color: #e3f2fd;">S/ {{ number_format($total, 2) }}</th>
        <th class="right" style="background-color: #e3f2fd;">S/ {{ number_format($total_importe_pagado, 2) }}</th>
        <th class="right" style="background-color: #e3f2fd;">S/ {{ number_format($total_importe_por_pagar, 2) }}</th>
      </tr>
    </tfoot>
  </table>
</body>
</html>
