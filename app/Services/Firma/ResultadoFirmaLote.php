<?php

namespace App\Services\Firma;

/**
 * Resumen de "Firmar todos": qué se firmó y qué no (con el motivo).
 */
final class ResultadoFirmaLote
{
    /**
     * @param  list<string>  $firmados  Nombres de documento.
     * @param  array<string, string>  $fallidos  Nombre de documento => motivo.
     */
    public function __construct(
        public readonly array $firmados,
        public readonly array $fallidos,
    ) {}

    public function resumen(): string
    {
        $total = count($this->firmados) + count($this->fallidos);
        $texto = count($this->firmados)." de {$total} documento(s) firmados.";

        foreach ($this->fallidos as $documento => $motivo) {
            $texto .= " «{$documento}»: {$motivo}";
        }

        return $texto;
    }
}
