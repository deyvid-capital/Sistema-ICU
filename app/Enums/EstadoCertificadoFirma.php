<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Ciclo de vida de una firma digital (.p12) asignada a un usuario.
 *
 * Ver Docs/firma-digital/03-seguridad.md.
 */
enum EstadoCertificadoFirma: string implements HasColor, HasLabel
{
    /** El admin subió el .p12; falta que el titular lo active con sus contraseñas. */
    case PendienteActivacion = 'pendiente_activacion';

    case Activo = 'activo';

    /** La contraseña del sistema se restableció sin conocer la anterior. */
    case RequiereReactivacion = 'requiere_reactivacion';

    /** Sustituido por la renovación anual. */
    case Reemplazado = 'reemplazado';

    /** Asignación eliminada por un admin. */
    case Revocado = 'revocado';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendienteActivacion => 'Pendiente de activación',
            self::Activo => 'Activo',
            self::RequiereReactivacion => 'Requiere reactivación',
            self::Reemplazado => 'Reemplazado',
            self::Revocado => 'Revocado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PendienteActivacion => 'gray',
            self::Activo => 'success',
            self::RequiereReactivacion => 'warning',
            self::Reemplazado => 'gray',
            self::Revocado => 'danger',
        };
    }

    /**
     * Estados que siguen vigentes como asignación (no son historial).
     *
     * @return list<self>
     */
    public static function vigentes(): array
    {
        return [self::PendienteActivacion, self::Activo, self::RequiereReactivacion];
    }
}
