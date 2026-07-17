<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoEquipo: string implements HasColor, HasLabel
{
    case SinAsignar = 'sin asignar';
    case Asignado = 'asignado';
    case EnMantenimiento = 'en mantenimiento';
    case Resguardo = 'resguardo';
    case Baja = 'baja';

    public function getLabel(): string
    {
        return match ($this) {
            self::SinAsignar => 'Sin asignar',
            self::Asignado => 'Asignado',
            self::EnMantenimiento => 'En mantenimiento',
            self::Resguardo => 'Resguardo',
            self::Baja => 'Baja',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SinAsignar => 'warning',
            self::Asignado => 'success',
            self::EnMantenimiento => 'info',
            self::Resguardo => 'gray',
            self::Baja => 'danger',
        };
    }

    public function isDisponibleParaAsignacion(): bool
    {
        return $this === self::SinAsignar;
    }
}
