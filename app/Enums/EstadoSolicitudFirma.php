<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado de un envío a firmar. Ver Docs/firma-digital/04-flujo-de-firma-y-bloqueo.md.
 */
enum EstadoSolicitudFirma: string implements HasColor, HasLabel
{
    /** Documentos y firmantes elegidos; falta ubicar los recuadros y enviar. */
    case Borrador = 'borrador';

    case EnCurso = 'en_curso';

    /** Todos los documentos quedaron completados o rechazados. */
    case Finalizada = 'finalizada';

    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::EnCurso => 'En curso',
            self::Finalizada => 'Finalizada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::EnCurso => 'warning',
            self::Finalizada => 'success',
            self::Cancelada => 'danger',
        };
    }
}
