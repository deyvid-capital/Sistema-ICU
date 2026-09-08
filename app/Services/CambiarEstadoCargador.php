<?php

namespace App\Services;

use App\Enums\EstadoCargador;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cambia el estado de un cargador cerrando, si procede, la asignación vigente.
 *
 * «Asignado», «Prestado temporalmente» y «Cedido» salen de las asignaciones.
 * Cualquier estado elegido a mano cierra la asignación abierta con la fecha del día.
 */
class CambiarEstadoCargador
{
    public function asignacionActiva(Cargador $cargador): ?AsignacionCargador
    {
        return AsignacionCargador::query()
            ->with('equipo')
            ->where('serial_cargador', $cargador->getKey())
            ->whereNull('fecha_fin')
            ->latest('fecha_inicio')
            ->first();
    }

    public function requiereConfirmacion(Cargador $cargador, EstadoCargador $nuevo): bool
    {
        if (! $nuevo->esSeleccionableManualmente() || $nuevo === $cargador->estado) {
            return false;
        }

        return $this->asignacionActiva($cargador) !== null;
    }

    public function ejecutar(Cargador $cargador, EstadoCargador $nuevo): ?AsignacionCargador
    {
        if ($cargador->trashed()) {
            throw new RuntimeException('No se puede cambiar el estado de un cargador que está en la papelera.');
        }

        if (! $nuevo->esSeleccionableManualmente()) {
            throw new RuntimeException("El estado «{$nuevo->getLabel()}» se asigna al asignar el cargador y no puede elegirse a mano.");
        }

        return DB::transaction(function () use ($cargador, $nuevo): ?AsignacionCargador {
            $cerrada = $this->cerrarAsignacionActiva($cargador);

            $cargador->update(['estado' => $nuevo]);

            return $cerrada;
        });
    }

    private function cerrarAsignacionActiva(Cargador $cargador): ?AsignacionCargador
    {
        $activa = $this->asignacionActiva($cargador);

        if ($activa === null) {
            return null;
        }

        $activa->update([
            'fecha_fin' => $this->fechaCierre($activa->fecha_inicio),
        ]);

        return $activa;
    }

    private function fechaCierre(?CarbonInterface $fechaInicio): CarbonImmutable
    {
        $hoy = CarbonImmutable::now()->startOfDay();

        if ($fechaInicio === null) {
            return $hoy;
        }

        return $hoy->lt($fechaInicio)
            ? CarbonImmutable::parse($fechaInicio)
            : $hoy;
    }
}
