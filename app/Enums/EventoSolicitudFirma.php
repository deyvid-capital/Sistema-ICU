<?php

namespace App\Enums;

/**
 * Avisos sobre el avance de un envío a firmar (ver Docs/firma-digital/06-notificaciones.md).
 */
enum EventoSolicitudFirma: string
{
    case Realizada = 'realizada';
    case Finalizada = 'finalizada';
    case Rechazada = 'rechazada';
    case Cancelada = 'cancelada';
    case Vencida = 'vencida';

    /**
     * Canales previstos; luego se filtran por las preferencias del usuario.
     *
     * @return list<string>
     */
    public function canales(): array
    {
        return match ($this) {
            self::Realizada, self::Cancelada => ['database'],
            self::Finalizada, self::Rechazada, self::Vencida => ['database', 'mail'],
        };
    }

    public function icono(): string
    {
        return match ($this) {
            self::Realizada => 'heroicon-o-pencil-square',
            self::Finalizada => 'heroicon-o-check-badge',
            self::Rechazada => 'heroicon-o-x-circle',
            self::Cancelada => 'heroicon-o-no-symbol',
            self::Vencida => 'heroicon-o-clock',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Realizada => 'info',
            self::Finalizada => 'success',
            self::Rechazada, self::Vencida => 'danger',
            self::Cancelada => 'gray',
        };
    }
}
