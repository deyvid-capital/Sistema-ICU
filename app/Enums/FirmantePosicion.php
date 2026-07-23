<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FirmantePosicion: string implements HasLabel
{
    case Izquierda = 'izquierda';
    case Centro = 'centro';
    case Derecha = 'derecha';

    public function getLabel(): string
    {
        return match ($this) {
            self::Izquierda => 'Izquierda',
            self::Centro => 'Centro',
            self::Derecha => 'Derecha',
        };
    }
}
