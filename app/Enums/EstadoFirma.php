<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado del recuadro de un firmante en un documento.
 */
enum EstadoFirma: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';

    case Firmado = 'firmado';

    case Rechazado = 'rechazado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Firmado => 'Firmado',
            self::Rechazado => 'Rechazado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Firmado => 'success',
            self::Rechazado => 'danger',
        };
    }
}
