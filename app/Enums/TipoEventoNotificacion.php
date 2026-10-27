<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoEventoNotificacion: string implements HasLabel
{
    case EventoCritico = 'evento_critico';
    case FirmaDigital = 'firma_digital';

    public function getLabel(): string
    {
        return match ($this) {
            self::EventoCritico => 'Eventos críticos del sistema',
            self::FirmaDigital => 'Firma digital',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::EventoCritico => 'Asignaciones, eliminaciones, cargadores dañados, documentos y otros avisos.',
            self::FirmaDigital => 'Documentos por firmar, recordatorios, firmas realizadas, completadas, rechazadas o canceladas, activación y vencimiento de la firma.',
        };
    }

    /**
     * Qué deja de pasar si se apaga un canal de este tipo (se muestra en la pantalla de Seguridad).
     */
    public function avisoAlDesactivar(): ?string
    {
        return match ($this) {
            self::EventoCritico => null,
            self::FirmaDigital => 'Con un canal apagado, este usuario no recibe por ese canal ningún aviso de firma digital: documentos por firmar, recordatorios, firmas realizadas, completadas, rechazadas o canceladas, activación o reactivación de su firma y vencimiento de su certificado. Si es administrador, tampoco le llegan los avisos de firma de otros usuarios. Nada se bloquea: sus firmas pendientes siguen esperando y las ve en Firma digital → Pendientes de mi firma. Los avisos que no se enviaron mientras estuvo apagado no se recuperan al volver a encenderlo.',
        };
    }
}
