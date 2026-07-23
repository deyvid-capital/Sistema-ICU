<?php

namespace App\Enums;

enum CelularFormularioPdfTipo: string
{
    case Entrega = 'entrega';
    case Recepcion = 'recepcion';

    /**
     * @return view-string
     */
    public function view(): string
    {
        return match ($this) {
            self::Entrega => 'PlantillasPdf.FormEntregaCelular',
            self::Recepcion => 'PlantillasPdf.FormRecepcionCelular',
        };
    }

    public function nombreOficial(): string
    {
        return match ($this) {
            self::Entrega => 'Formulario de entrega de celular',
            self::Recepcion => 'Formulario de recepción de celular',
        };
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
        return match ($this) {
            self::Entrega => 'FUC-',
            self::Recepcion => 'FURC-',
        };
    }

    public function storageDirectory(): string
    {
        return match ($this) {
            self::Entrega => 'pdf-celulares/entrega',
            self::Recepcion => 'pdf-celulares/recepcion',
        };
    }

    public function pdfRelativePath(string $numero): string
    {
        return $this->storageDirectory().'/'.$this->pdfFilenamePrefix().$numero.'.pdf';
    }
}
