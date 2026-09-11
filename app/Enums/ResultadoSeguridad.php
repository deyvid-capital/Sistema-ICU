<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Desenlace de un evento de seguridad. No se persiste: se deriva de la columna
 * `event` con EventoSeguridad::resultado(), para que reclasificar un evento no
 * deje filas antiguas con un resultado desincronizado.
 */
enum ResultadoSeguridad: string implements HasColor, HasLabel
{
    case Exito = 'exito';
    case Fallo = 'fallo';
    case Bloqueo = 'bloqueo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Exito => 'Exitoso',
            self::Fallo => 'Fallido',
            self::Bloqueo => 'Bloqueo',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Exito => 'success',
            self::Fallo => 'warning',
            self::Bloqueo => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::cases() as $resultado) {
            $opciones[$resultado->value] = $resultado->getLabel();
        }

        return $opciones;
    }
}
