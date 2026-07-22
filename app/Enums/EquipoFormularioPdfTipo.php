<?php

namespace App\Enums;

enum EquipoFormularioPdfTipo: string
{
    case Entrega = 'entrega';
    case Recepcion = 'recepcion';
    case NotaSalida = 'nota_salida';
    case NotaRecepcion = 'nota_recepcion';

    /**
     * @return view-string
     */
    public function view(): string
    {
        return match ($this) {
            self::Entrega => 'PlantillasPdf.FormEntrega',
            self::Recepcion => 'PlantillasPdf.FormRecepcion',
            self::NotaSalida => 'PlantillasPdf.FormNotaSalida',
            self::NotaRecepcion => 'PlantillasPdf.FormRecepcionExterna',
        };
    }

    public function nombreOficial(): string
    {
        return match ($this) {
            self::Entrega => 'Formulario de entrega',
            self::Recepcion => 'Formulario de recepción',
            self::NotaSalida => 'Nota de salida',
            self::NotaRecepcion => 'Nota de recepción externa',
        };
    }

    public function label(): string
    {
        return $this->nombreOficial();
    }

    public function pdfFilenamePrefix(): string
    {
        return match ($this) {
            self::Entrega => 'FUE-',
            self::Recepcion => 'FUR-',
            self::NotaSalida => 'NotaSalida-',
            self::NotaRecepcion => 'NotaRecepcion-',
        };
    }

    public function storageDirectory(): string
    {
        return match ($this) {
            self::Entrega => 'pdf-equipos/entrega',
            self::Recepcion => 'pdf-equipos/recepcion',
            self::NotaSalida => 'pdf-equipos/nota-salida',
            self::NotaRecepcion => 'pdf-equipos/nota-recepcion',
        };
    }

    public function pdfRelativePath(string $numero): string
    {
        return $this->storageDirectory().'/'.$this->pdfFilenamePrefix().$numero.'.pdf';
    }
}
