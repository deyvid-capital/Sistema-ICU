<?php

namespace App\Services\Firma;

/**
 * Recursos compartidos del sello visible.
 */
class SelloFirma
{
    public static function logo(): ?string
    {
        $ruta = (string) config('firma.sello.logo');

        return is_file($ruta) ? (string) file_get_contents($ruta) : null;
    }
}
