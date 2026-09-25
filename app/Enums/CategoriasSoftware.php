<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CategoriasSoftware: string implements HasColor, HasLabel
{
    case VPN = 'vpn';
    case Navegadores = 'navegadores';
    case PDF = 'pdf';
    case Antivirus = 'antivirus';
    case Compresion = 'compresion';
    case Microsoft = 'microsoft';
    case AutenticacionMultifactor = 'autenticacion_multifactor';
    case Impresora = 'impresora';
    case Scanner = 'scanner';
    case SoftwareVPN = 'software_vpn';
    case SoporteRemoto = 'soporte_remoto';
    case SoftwareEscaner = 'software_escaner';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::VPN => 'VPN',
            self::Navegadores => 'Navegadores',
            self::PDF => 'PDF',
            self::Antivirus => 'Antivirus',
            self::Compresion => 'Compresión',
            self::Microsoft => 'Microsoft',
            self::AutenticacionMultifactor => 'Autenticación multifactor',
            self::Impresora => 'Impresora',
            self::Scanner => 'Scanner',
            self::SoftwareVPN => 'Software VPN',
            self::SoporteRemoto => 'Soporte remoto',
            self::SoftwareEscaner => 'Software escáner',
            self::Otro => 'Otro',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::VPN => 'info',
            self::Navegadores => 'primary',
            self::PDF => 'danger',
            self::Antivirus => 'success',
            self::Compresion => 'warning',
            self::Microsoft => 'info',
            self::AutenticacionMultifactor => 'success',
            self::Impresora => 'gray',
            self::Scanner => 'gray',
            self::SoftwareVPN => 'primary',
            self::SoporteRemoto => 'warning',
            self::SoftwareEscaner => 'info',
            self::Otro => 'gray',
        };
    }
}
