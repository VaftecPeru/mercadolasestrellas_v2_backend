<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exportar Reporte de Pagos</title>
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
  <h2>MERCADO LAS ESTRELLAS - REPORTE DE PAGOS</h2>
  <table>
    <tbody>
      <td><strong>Nombre del socio</strong></td>
      <td> {{ $nombre_socio }}</td>
    </tbody>
  </table>
  <table>
    <thead>
      <tr>
        <th>Año</th>
        <th>Mes</th>
        <th>Fec. Pago</th>
        <th>Servicios</th>
        <th>Monto (S/.)</th>
        <th>Pago (S/.)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($pagos as $pago)
        @php $pagoCount = count($pago['detalles']); @endphp
        @foreach($pago['detalles'] as $key => $detalle)
          <tr>
            <td align="center">{{ $pago['anio'] }}</td>
            <td align="center">{{ $pago['mes'] }}</td>
            <td align="center">{{ $pago['fecha'] }}</td>
            <td>{{ $detalle['servicio_nombre'] }}</td>
            <td class="right">{{ number_format($detalle['importe'], 2) }}</td>
            <td class="right" style="background-color: #fafafa; font-weight: bold;">
              {{ $key === $pagoCount - 1 ? 'S/ ' . number_format($pago['total'], 2) : '' }}
            </td>
          </tr>
        @endforeach
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th colspan="5" class="right">TOTAL GENERAL:</th>
        <th class="right" style="background-color: #e3f2fd;">S/ {{ number_format($total, 2) }}</th>
      </tr>
    </tfoot>
  </table>
</body>
</html>