<?php

namespace App\Support;

use App\Models\DetallePagos;
use Illuminate\Support\Facades\DB;


class ReporteResumen
{
    public const SERVICIOS_GASTOS_ADMINISTRATIVOS = [1274, 1275, 1313];

    public const SERVICIO_MULTA_INASISTENCIA = 1328;

    public const SERVICIO_CUOTA_EXTRAORDINARIA = 1212;

    /**
     * Consulta base del Reporte Resumen, agrupada por pago.
     */
    public static function query(int $idPuesto)
    {
        $gastosAdministrativos = implode(', ', self::SERVICIOS_GASTOS_ADMINISTRATIVOS);

        return DetallePagos::select(
            'b.serie',
            'b.numero_pago',
            DB::raw("concat(b.serie, '-', b.numero_pago) as serie_numero"),
            DB::raw('b.total_pago as importe_ingreso'),
            DB::raw("sum(case when detalle_pagos.id_servicio in ({$gastosAdministrativos}) then detalle_pagos.importe else 0 end) as importe_gastos_administrativo"),
            DB::raw("sum(case when s.tipo_servicio = 1 and detalle_pagos.id_servicio not in ({$gastosAdministrativos}) then detalle_pagos.importe else 0 end) as importe_otros_servicios"),
            DB::raw('sum(case when detalle_pagos.id_servicio = '.self::SERVICIO_MULTA_INASISTENCIA.' then detalle_pagos.importe else 0 end) as importe_multas_inasistencia'),
            DB::raw('max(case when pago_banco.id_pagobanco is not null then b.total_pago else 0 end) as importe_pagos_banco'),
            DB::raw('max(case when pago_banco.id_pagobanco is null then b.total_pago else 0 end) as importe_pagos_efectivo'),
            DB::raw('sum(case when detalle_pagos.id_servicio = '.self::SERVICIO_CUOTA_EXTRAORDINARIA.' then detalle_pagos.importe else 0 end) as importe_cuotas_extraordinarias'),
            DB::raw('b.total_pago as importe_total')
        )
            ->join('pagos as b', 'detalle_pagos.id_pago', 'b.id_pago')
            ->leftJoin('pago_banco', 'pago_banco.id_pagobanco', 'b.id_pago')
            ->leftJoin('servicios as s', 'detalle_pagos.id_servicio', 's.id_servicio')
            ->where('detalle_pagos.id_puesto', $idPuesto)
            ->groupBy('b.total_pago', 'b.serie', 'b.numero_pago', 'b.id_pago');
    }
}
