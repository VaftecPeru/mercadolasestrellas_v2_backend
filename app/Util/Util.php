<?php

namespace App\Util;

class Util
{
    public static function compareDateYear($fecha, $anho)
    {
        if (!is_numeric($anho) || strlen($anho) !== 4) {
            throw new \InvalidArgumentException("Año inválido: $anho");
        }

        return "YEAR($fecha) = " . intval($anho);
    }

    public static function compareDateMonth($fecha, $mes)
    {
        if (!is_numeric($mes) || intval($mes) < 1 || intval($mes) > 12) {
            throw new \InvalidArgumentException("Mes inválido: $mes");
        }

        return "MONTH($fecha) = " . intval($mes);
    }

    public static function sumaColArrayObjFormat($arrayObject, $columna)
    {
        $total = array_sum(array_column($arrayObject, $columna));
        return number_format($total, 2, '.', '');
    }
}
