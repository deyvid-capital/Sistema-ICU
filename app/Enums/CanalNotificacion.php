<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CanalNotificacion: string implements HasLabel
{
    case Database = 'database';
    case Mail = 'mail';

    public function getLabel(): string
    {
        return match ($this) {
            self::Database => 'Campana (panel)',
            self::Mail => 'Correo',
        };
    }
}
