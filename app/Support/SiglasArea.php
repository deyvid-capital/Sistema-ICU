<?php

namespace App\Support;

/**
 * Siglas de un área: la inicial de cada palabra significativa.
 * "Inversiones Alternativas" → "IA". Nombra el perfil de internet (Wi-Fi)
 * del área en el formulario de alta/baja y en la matriz de perfiles.
 */
class SiglasArea
{
    public static function de(string $area): string
    {
        $partes = preg_split('/\s+/u', trim($area)) ?: [];

        $siglas = collect($partes)
            ->filter(fn (string $parte): bool => mb_strlen($parte) > 2 || ctype_upper($parte))
            ->map(fn (string $parte): string => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        if ($siglas === '') {
            $siglas = mb_strtoupper(mb_substr(preg_replace('/\s+/u', '', $area) ?? $area, 0, 2));
        }

        return $siglas;
    }

    /**
     * "Inversiones Alternativas (IA)".
     */
    public static function conNombre(string $area): string
    {
        return $area.' ('.self::de($area).')';
    }
}
