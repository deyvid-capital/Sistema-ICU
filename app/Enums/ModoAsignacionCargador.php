<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModoAsignacionCargador: string implements HasLabel
{
    case Temporal = 'temporal';
    case Reasignacion = 'reasignacion';

    public function getLabel(): string
    {
        return match ($this) {
            self::Temporal => 'Prestado temporalmente',
            self::Reasignacion => 'Cedido al equipo afectado',
        };
    }

    public function toEstadoCargador(): EstadoCargador
    {
        return match ($this) {
            self::Temporal => EstadoCargador::Temporal,
            self::Reasignacion => EstadoCargador::Reasignacion,
        };
    }
}
