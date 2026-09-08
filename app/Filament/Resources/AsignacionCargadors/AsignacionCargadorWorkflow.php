<?php

namespace App\Filament\Resources\AsignacionCargadors;

use App\Enums\EstadoCargador;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use App\Services\MovimientoCargador;

/**
 * Asignación normal de cargadores: detectar la activa y actualizar el estado.
 * El prestamo y la cesión viven en {@see MovimientoCargador}.
 */
class AsignacionCargadorWorkflow
{
    public static function asignacionActivaDelCargador(string $serial, ?int $ignoreId = null): ?AsignacionCargador
    {
        return AsignacionCargador::query()
            ->with('equipo')
            ->where('serial_cargador', $serial)
            ->whereNull('fecha_fin')
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->latest('fecha_inicio')
            ->first();
    }

    public static function cargadorEstaBloqueado(Cargador $cargador): bool
    {
        $estado = $cargador->estado instanceof EstadoCargador
            ? $cargador->estado
            : EstadoCargador::tryFrom((string) $cargador->estado);

        return $estado?->estaBloqueado() ?? false;
    }

    public static function actualizarEstadoCargador(string $serial, bool $cerrada = false): void
    {
        $cargador = Cargador::query()->find($serial);

        if (! $cargador || static::cargadorEstaBloqueado($cargador)) {
            return;
        }

        if ($cerrada) {
            $sigueActiva = AsignacionCargador::query()
                ->where('serial_cargador', $serial)
                ->whereNull('fecha_fin')
                ->exists();

            if (! $sigueActiva) {
                $cargador->update(['estado' => EstadoCargador::Disponible]);
            }

            return;
        }

        $cargador->update(['estado' => EstadoCargador::Asignado]);
    }
}
