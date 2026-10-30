<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Estado de firma de un PDF en su módulo de origen (entrega, recepción, movimiento...).
 */
enum EstadoFirmaOrigen: string implements HasColor, HasIcon, HasLabel
{
    case SinFirma = 'sin_firma';
    case EnFirma = 'en_firma';
    case Firmado = 'firmado';
    case FirmadoManual = 'firmado_manual';
    case Rechazado = 'rechazado';

    public function getLabel(): string
    {
        return match ($this) {
            self::SinFirma => 'Sin firma',
            self::EnFirma => 'En firma',
            self::Firmado => 'Firmado',
            self::FirmadoManual => 'Firmado a mano',
            self::Rechazado => 'Rechazado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SinFirma => 'gray',
            self::EnFirma => 'warning',
            self::Firmado, self::FirmadoManual => 'success',
            self::Rechazado => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::SinFirma => Heroicon::OutlinedMinusCircle,
            self::EnFirma => Heroicon::OutlinedClock,
            self::Firmado => Heroicon::OutlinedCheckBadge,
            self::FirmadoManual => Heroicon::OutlinedDocumentCheck,
            self::Rechazado => Heroicon::OutlinedXCircle,
        };
    }

    /**
     * Se puede enviar a firmar (de nuevo).
     */
    public function esFirmable(): bool
    {
        return in_array($this, self::firmables(), true);
    }

    public function estaFirmado(): bool
    {
        return $this === self::Firmado || $this === self::FirmadoManual;
    }

    /**
     * @return list<self>
     */
    public static function firmables(): array
    {
        return [self::SinFirma, self::Rechazado];
    }
}
