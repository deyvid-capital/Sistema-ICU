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
            self::Temporal => 'Temporal (contingencia)',
            self::Reasignacion => 'Reasignación (permanente)',
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
