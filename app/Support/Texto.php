<?php

namespace App\Support;

class Texto
{
    
    public static function capitalizarNombre($texto): string
    {
        $texto = preg_replace('/\s+/u', ' ', trim((string) $texto));

        if ($texto === '' || $texto === null) {
            return '';
        }

        $texto = mb_strtolower($texto, 'UTF-8');

        return preg_replace_callback('/(^|[\s\-\'])(\p{L})/u', function ($m) {
            return $m[1].mb_strtoupper($m[2], 'UTF-8');
        }, $texto);
    }
}
