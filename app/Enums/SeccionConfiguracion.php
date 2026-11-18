<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Páginas del cluster de Configuración que registran actividad. El valor se
 * guarda en `activity_log.properties.seccion`.
 */
enum SeccionConfiguracion: string implements HasLabel
{
    /**
     * @deprecated La página se retiró (PLAN-002): la apariencia vive en el plugin. Se conserva
     *             porque hay filas de auditoría antiguas con este valor.
     */
    case TemaColores = 'tema_colores';

    /**
     * @deprecated La página se retiró (PLAN-002): la marca vive en el plugin. Se conserva
     *             porque hay filas de auditoría antiguas con este valor.
     */
    case Marca = 'marca';

    case Apariencia = 'apariencia';
    case Archivos = 'archivos';
    case Correo = 'correo';
    case FirmaDigital = 'firma_digital';

    public function getLabel(): string
    {
        return match ($this) {
            self::TemaColores => 'Tema y colores',
            self::Marca => 'Marca e identidad',
            self::Apariencia => 'Apariencia',
            self::Archivos => 'Archivos y almacenamiento',
            self::Correo => 'Correo SMTP',
            self::FirmaDigital => 'Firma digital',
        };
    }

    public function descripcionActualizado(): string
    {
        return match ($this) {
            self::TemaColores => 'Tema y colores actualizados',
            self::Marca => 'Marca e identidad actualizadas',
            self::Apariencia => 'Apariencia actualizada',
            self::Archivos => 'Configuración de archivos actualizada',
            self::Correo => 'Configuración de correo actualizada',
            self::FirmaDigital => 'Configuración de firma digital actualizada',
        };
    }

    public function descripcionRestaurado(): string
    {
        return match ($this) {
            self::TemaColores => 'Tema y colores restaurados',
            self::Marca => 'Marca e identidad restauradas',
            self::Apariencia => 'Apariencia restaurada',
            self::Archivos => 'Configuración de archivos restaurada',
            self::Correo => 'Configuración de correo restaurada',
            self::FirmaDigital => 'Configuración de firma digital restaurada',
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
