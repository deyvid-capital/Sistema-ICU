<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado de cada PDF dentro de un envío a firmar.
 */
enum EstadoDocumentoFirma: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';

    case EnCurso = 'en_curso';

    /** Tiene todas sus firmas. */
    case Completado = 'completado';

    case Rechazado = 'rechazado';

    case Cancelado = 'cancelado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::EnCurso => 'En curso',
            self::Completado => 'Completado',
            self::Rechazado => 'Rechazado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::EnCurso => 'warning',
            self::Completado => 'success',
            self::Rechazado, self::Cancelado => 'danger',
        };
    }
}
