<?php

namespace App\Enums;

use App\Models\Movimiento;
use Illuminate\Support\Str;

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

    /**
     * FUA para movimientos de Alta y FUB para los de Baja.
     */
    public function pdfFilenamePrefix(Movimiento $movimiento): string
    {
        return $movimiento->tipo === 'Baja' ? 'FUB' : 'FUA';
    }

    public function storageDirectory(): string
    {
        return 'pdf-movimientos';
    }

    /**
     * Nombre del archivo con el formato «FUA-Nombre Apellido-dd-mm-aaaa.pdf».
     */
    public function pdfFilename(Movimiento $movimiento): string
    {
        $usuario = $movimiento->usuario;

        $nombreCompleto = trim(
            Str::before(Str::squish((string) $usuario?->nombres), ' ')
            .' '.
            Str::before(Str::squish((string) $usuario?->apellidos), ' ')
        );

        $partes = array_filter(
            [
                $this->pdfFilenamePrefix($movimiento),
                str_replace(['/', '\\'], '', $nombreCompleto),
                $movimiento->fecha?->format('d-m-Y'),
            ],
            fn (?string $parte): bool => filled($parte),
        );

        return implode('-', $partes).'.pdf';
    }

    public function pdfRelativePath(Movimiento $movimiento): string
    {
        return $this->storageDirectory().'/'.$this->pdfFilename($movimiento);
    }
}
