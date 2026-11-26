<?php

namespace App\Enums;

use App\Models\UsuarioExterno;

enum UsuarioExternoFormularioPdfTipo: string
{
    case Formulario = 'formulario';

    /**
     * @return view-string
     */
    public function view(): string
    {
        return 'PlantillasPdf.FormUsuarioExterno';
    }

    public function nombreOficial(): string
    {
        return 'Formulario de Usuario Externo';
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

    public function storageDirectory(): string
    {
        return 'pdf-usuarios-externos';
    }

    /**
     * Nombre del archivo con el formato «UsuarioExterno-001-2026.pdf».
     */
    public function pdfFilename(UsuarioExterno $usuarioExterno): string
    {
        return 'UsuarioExterno-'.$usuarioExterno->numero.'.pdf';
    }

    public function pdfRelativePath(UsuarioExterno $usuarioExterno): string
    {
        return $this->storageDirectory().'/'.$this->pdfFilename($usuarioExterno);
    }
}
