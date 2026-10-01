<?php

namespace App\Support;

class FiltroTexto
{
    /**
     * Normaliza un texto para búsquedas por nombre (sin tildes y con espacios como comodín).
     * Replica el comportamiento usado en SocioController y PagoController.
     */
    public static function normalizarNombre(?string $texto): string
    {
        if (is_null($texto)) {
            return '';
        }

        $texto = strtr(utf8_decode($texto), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
        $texto = strtr(utf8_decode($texto), utf8_decode('àáâãäçèéêëìíîïññòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiin?ooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');

        return str_replace(' ', '%', $texto);
    }
}
