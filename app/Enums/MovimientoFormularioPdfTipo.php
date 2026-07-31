<?php

namespace App\Enums;

enum MovimientoFormularioPdfTipo: string
{
    case AltaBaja = 'alta_baja';

    /**
     * @return view-string
     */
    public function view(): string
    {
        return 'PlantillasPdf.FormAltaBaja';
    }

    public function nombreOficial(): string
    {
        return 'Formulario de alta/baja de usuario';
    }

    /**
     * Resuelve el tipo a partir del nombre oficial guardado en el PDF.
     */
    public static function tryFromNombreOficial(?string $nombre): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->nombreOficial() === $nombre) {
                return $case;
            }
        }

        return null;
    }

    public function label(): string
    {
        return $this->nombreOficial();
    }

    public function pdfFilenamePrefix(): string
    {
        return 'FU-';
    }

    public function storageDirectory(): string
    {
        return 'pdf-movimientos';
    }

    public function pdfRelativePath(string|int $movimientoId): string
    {
        return $this->storageDirectory().'/'.$this->pdfFilenamePrefix().$movimientoId.'.pdf';
    }
}
