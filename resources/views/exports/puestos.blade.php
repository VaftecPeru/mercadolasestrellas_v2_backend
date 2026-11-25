<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exportar Listado Puestos</title>
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
      white-space: nowrap;
    }

    thead {
      display: table-header-group;
    }

    h2 {
      text-align: center;
      margin-top: 20px;
    }

    .right { text-align: right; }
  </style>
</head>
<body>
  <h2>MERCADO LAS ESTRELLAS - LISTA DE PUESTOS</h2>
  <table>
    <thead>
      <tr>
        <th>Bloque</th>
        <th>N° Puesto</th>
        <th>Área (m2)</th>
        <th>Giro de Negocio</th>
        <th>Nombre del socio</th>
        <th>Inquilino</th>
        <th>Estado</th>
        <th>Fecha Registro</th>
      </tr>
    </thead>
    <tbody>
      @foreach($puestos as $puesto)
        <tr>
          <td align="center">{{ $puesto['bloque'] }}</td>
          <td align="center">{{ $puesto['puesto'] }}</td>
          <td class="right">{{ $puesto['area'] }}</td>
          <td>{{ $puesto['giro'] }}</td>
          <td>{{ $puesto['socio'] }}</td>
          <td>{{ $puesto['inquilino'] }}</td>
          <td align="center">{{ $puesto['estado'] }}</td>
          <td align="center">{{ $puesto['fecha_registro'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>