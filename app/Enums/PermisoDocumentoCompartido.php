<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Permiso que el contacto externo tiene sobre los documentos compartidos
 * (SharePoint u OneDrive) de un usuario externo.
 */
enum PermisoDocumentoCompartido: string implements HasColor, HasLabel
{
    case Lectura = 'lectura';
    case PuedeVerNoDescargar = 'puede_ver_no_descargar';
    case Edicion = 'edicion';

    public function getLabel(): string
    {
        return match ($this) {
            self::Lectura => 'Solo lectura',
            self::PuedeVerNoDescargar => 'puede ver pero no descargar',
            self::Edicion => 'Edición',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Lectura => 'gray',
            self::PuedeVerNoDescargar => 'info',
            self::Edicion => 'warning',
        };
    }
}
