<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoAcceso: string implements HasLabel
{
    case Indefinido = 'indefinido';
    case Definido = 'definido';

    public function getLabel(): string
    {
        return match ($this) {
            self::Indefinido => 'Indefinido',
            self::Definido => 'Hasta una fecha',
        };
    }
}
