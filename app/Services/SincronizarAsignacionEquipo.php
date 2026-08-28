<?php

namespace App\Services;

use App\Enums\EstadoEquipo;
use App\Models\AsignacionEquipo;

class SincronizarAsignacionEquipo
{
    public function sincronizarEstadoEquipo(AsignacionEquipo $asignacion): void
    {
        $equipo = $asignacion->equipo;

        if (! $equipo) {
            return;
        }

        $estado = $equipo->estado instanceof EstadoEquipo
            ? $equipo->estado
            : EstadoEquipo::tryFrom((string) $equipo->estado);

        // Un equipo en mantenimiento, resguardo o baja conserva su estado: cerrar
        // la asignación no debe devolverlo al inventario asignable.
        if ($estado?->estaBloqueado() ?? false) {
            return;
        }

        if (filled($asignacion->fecha_fin)) {
            $equipo->update(['estado' => EstadoEquipo::SinAsignar]);

            return;
        }

        $equipo->update(['estado' => EstadoEquipo::Asignado]);
    }
}
