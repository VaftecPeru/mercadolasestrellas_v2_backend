<?php

namespace App\Support;

class Comprobante
{
    /**
     * Normaliza un comprobante al formato estándar: serie de 4 dígitos + número de 8 dígitos.
     * Ej.: serie vacía con numero "0011" => "0000-00000011".
     */
    public static function formatear($serie, $numero): string
    {
        $serie = trim((string) $serie);
        if ($serie === '') {
            $serie = '0000';
        }

        $numero = trim((string) $numero);
        if (strlen($numero) < 8) {
            $numero = str_pad($numero, 8, '0', STR_PAD_LEFT);
        }

        return $serie.'-'.$numero;
    }
}
