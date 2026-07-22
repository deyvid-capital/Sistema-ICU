<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoCargador: string implements HasColor, HasLabel
{
    case Disponible = 'disponible';
    case Asignado = 'asignado';
    case Temporal = 'temporal';
    case Reasignacion = 'reasignacion';
    case Dañado = 'dañado';
    case Baja = 'baja';

    public function getLabel(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Asignado => 'Asignado',
            self::Temporal => 'Temporal',
            self::Reasignacion => 'Reasignación',
            self::Dañado => 'Dañado',
            self::Baja => 'Baja',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Disponible => 'success',
            self::Asignado => 'info',
            self::Temporal => 'warning',
            self::Reasignacion => 'primary',
            self::Dañado => 'danger',
            self::Baja => 'gray',
        };
    }

    public function isLibreParaAsignacionDirecta(): bool
    {
        return $this === self::Disponible;
    }

    public function estaBloqueado(): bool
    {
        return in_array($this, [self::Dañado, self::Baja], true);
    }
}
