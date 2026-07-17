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

        if (filled($asignacion->fecha_fin)) {
            $equipo->update(['estado' => EstadoEquipo::SinAsignar]);

            return;
        }

        if (in_array($equipo->estado, [EstadoEquipo::EnMantenimiento, EstadoEquipo::Resguardo, EstadoEquipo::Baja], true)) {
            return;
        }

        $equipo->update(['estado' => EstadoEquipo::Asignado]);
    }
}
