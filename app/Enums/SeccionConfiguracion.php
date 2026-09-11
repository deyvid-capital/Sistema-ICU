<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Páginas del cluster de Configuración que registran actividad. El valor se
 * guarda en `activity_log.properties.seccion`.
 */
enum SeccionConfiguracion: string implements HasLabel
{
    case TemaColores = 'tema_colores';
    case Marca = 'marca';
    case Archivos = 'archivos';
    case Correo = 'correo';

    public function getLabel(): string
    {
        return match ($this) {
            self::TemaColores => 'Tema y colores',
            self::Marca => 'Marca e identidad',
            self::Archivos => 'Archivos y almacenamiento',
            self::Correo => 'Correo SMTP',
        };
    }

    public function descripcionActualizado(): string
    {
        return match ($this) {
            self::TemaColores => 'Tema y colores actualizados',
            self::Marca => 'Marca e identidad actualizadas',
            self::Archivos => 'Configuración de archivos actualizada',
            self::Correo => 'Configuración de correo actualizada',
        };
    }

    public function descripcionRestaurado(): string
    {
        return match ($this) {
            self::TemaColores => 'Tema y colores restaurados',
            self::Marca => 'Marca e identidad restauradas',
            self::Archivos => 'Configuración de archivos restaurada',
            self::Correo => 'Configuración de correo restaurada',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::cases() as $seccion) {
            $opciones[$seccion->value] = $seccion->getLabel();
        }

        return $opciones;
    }
}
