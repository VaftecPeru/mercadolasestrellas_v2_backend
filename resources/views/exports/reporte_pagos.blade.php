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
    <thead>
      <tr>
        <th>Nombre del socio</th>
        <th>Bloque</th>
        <th>Nro. Puesto</th>
        <th>Area</th>
        <th>Giro de negocio</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $nombre_socio }}</td>
        <td>{{ $nombre_bloque }}</td>
        <td>{{ $numero_puesto }}</td>
        <td>{{ $area }}</td>
        <td>{{ $giro_negocio }}</td>
      </tr>
  </table>
  <table>
    <thead>
      <tr>
        @if($modo_detalle ?? false)
          <th>Fec. Pago</th>
          <th>Comprobante</th>
          <th>Concepto</th>
          <th>Monto (S/.)</th>
        @else
          <th>Fec. Pago</th>
          <th>Servicios</th>
          <th>Total (S/.)</th>
          <th>Imp. Pagado (S/.)</th>
        @endif
      </tr>
    </thead>
    <tbody>
      @foreach($pagos as $pago)
        @php $pagoCount = count($pago['detalles']); @endphp
        @foreach($pago['detalles'] as $key => $detalle)
          @if($modo_detalle ?? false)
            <tr>
              <td align="center">{{ $key === 0 ? $pago['fecha'] : '' }}</td>
              <td align="center">{{ $key === 0 ? $pago['serie_numero'] : '' }}</td>
              <td>{{ $detalle['servicio_nombre'] }}</td>
              <td class="right">{{ $key === $pagoCount - 1 ? 'S/ ' . number_format($pago['total'], 2) : number_format($detalle['importe'], 2) }}</td>
            </tr>
          @else
            <tr>
              <td align="center">{{ $pago['fecha'] }}</td>
              <td>{{ $detalle['servicio_nombre'] }}</td>
              <td class="right">{{ number_format($detalle['importe'], 2) }}</td>
              <td class="right" style="background-color: #fafafa;">
                {{ $key === $pagoCount - 1 ? 'S/ ' . number_format($pago['total'], 2) : '' }}
              </td>
            </tr>
          @endif
        @endforeach
      @endforeach
    </tbody>
    <tfoot>
      @if($modo_detalle ?? false)
        <tr>
          <th colspan="3" class="right">Total (S/.)</th>
          <th class="right" style="background-color: #e3f2fd;">S/ {{ number_format($total_monto_detalle, 2) }}</th>
        </tr>
      @else
        <tr>
          <th colspan="2" class="right">Total (S/.)</th>
          <th class="right" style="background-color: #e3f2fd;">S/ {{ number_format($total_monto, 2) }}</th>
          <th class="right" style="background-color: #e3f2fd;">S/ {{ number_format($total, 2) }}</th>
        </tr>
      @endif
    </tfoot>
  </table>
</body>
</html>