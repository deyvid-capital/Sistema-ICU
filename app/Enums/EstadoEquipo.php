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
    case BajaTemporal = 'baja temporal';
    case BajaDefinitiva = 'baja definitiva';
    case Desarmado = 'desarmado';
    case Robo = 'robo';
    case Perdida = 'perdida';
    case Depreciado = 'depreciado';

    public function getLabel(): string
    {
        return match ($this) {
            self::SinAsignar => 'Sin asignar',
            self::Asignado => 'Asignado',
            self::EnMantenimiento => 'En mantenimiento',
            self::Resguardo => 'Resguardo',
            self::BajaTemporal => 'Baja temporal',
            self::BajaDefinitiva => 'Baja definitiva',
            self::Desarmado => 'Desarmado',
            self::Robo => 'Robo',
            self::Perdida => 'Pérdida',
            self::Depreciado => 'Depreciado por uso',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SinAsignar => 'warning',
            self::Asignado => 'success',
            self::EnMantenimiento => 'info',
            self::Resguardo => 'gray',
            self::BajaTemporal => 'warning',
            self::BajaDefinitiva => 'danger',
            self::Desarmado => 'gray',
            self::Robo => 'danger',
            self::Perdida => 'danger',
            self::Depreciado => 'gray',
        };
    }

    public function isDisponibleParaAsignacion(): bool
    {
        return $this === self::SinAsignar;
    }

    /**
     * Todo lo que no sea «sin asignar» ni «asignado» impide asignar el equipo.
     */
    public function estaBloqueado(): bool
    {
        return ! in_array($this, [self::SinAsignar, self::Asignado], true);
    }

    /**
     * Cualquier forma de baja, temporal o no.
     */
    public function esBaja(): bool
    {
        return in_array($this, [
            self::BajaTemporal,
            self::BajaDefinitiva,
            self::Desarmado,
            self::Robo,
            self::Perdida,
            self::Depreciado,
        ], true);
    }

    /**
     * Bajas sin retorno previsto: salir de ellas exige una advertencia extra.
     */
    public function esDefinitivo(): bool
    {
        return $this->esBaja() && $this !== self::BajaTemporal;
    }

    /**
     * «Asignado» se deriva de las asignaciones vigentes, nunca se elige a mano.
     * Cualquier otro estado, elegido manualmente, cierra la asignación activa.
     */
    public function esSeleccionableManualmente(): bool
    {
        return $this !== self::Asignado;
    }
}
