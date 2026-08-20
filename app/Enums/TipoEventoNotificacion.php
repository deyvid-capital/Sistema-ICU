<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoEventoNotificacion: string implements HasLabel
{
    case EventoCritico = 'evento_critico';

    public function getLabel(): string
    {
        return match ($this) {
            self::EventoCritico => 'Eventos críticos del sistema',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::EventoCritico => 'Asignaciones, eliminaciones, cargadores dañados, documentos y otros avisos.',
        };
    }
}
