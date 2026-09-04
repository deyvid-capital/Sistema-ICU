<?php

namespace App\Filament\Resources\AsignacionEquipos;

use App\Enums\EstadoEquipo;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use Illuminate\Support\Carbon;

/**
 * Lógica de negocio de asignación de equipos (creación y cierre).
 * Pensado para llamarse desde Create/Edit o RelationManagers.
 */
class AsignacionEquipoWorkflow
{
    public static function asignacionActivaDelEquipo(string $serviceTag, ?int $ignoreId = null): ?AsignacionEquipo
    {
        return AsignacionEquipo::query()
            ->with('usuario')
            ->where('service_tag', $serviceTag)
            ->whereNull('fecha_fin')
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->latest('fecha_inicio')
            ->first();
    }

    public static function equipoEstaBloqueado(Equipo $equipo): bool
    {
        $estado = $equipo->estado instanceof EstadoEquipo
            ? $equipo->estado
            : EstadoEquipo::tryFrom((string) $equipo->estado);

        return $estado?->estaBloqueado() ?? false;
    }

    public static function notaCierrePorEstado(
        EstadoEquipo $estado,
        Carbon|string|null $fecha = null,
    ): string {
        $fechaTexto = Carbon::parse($fecha ?? now())->format('d/m/Y');
        $estadoTexto = $estado->getLabel();

        return "<p><strong>Cierre por cambio de estado:</strong> el equipo pasó a <strong>{$estadoTexto}</strong> el {$fechaTexto}.</p>";
    }

    public static function appendObservacion(string|array|null $actual, string $nota): string
    {
        if (is_array($actual)) {
            $actual = '';
        }

        $actual = trim((string) ($actual ?? ''));

        return $actual === '' ? $nota : $actual.$nota;
    }

    public static function actualizarEstadoEquipo(string $serviceTag, bool $cerrada = false): void
    {
        $equipo = Equipo::query()->find($serviceTag);

        if (! $equipo || static::equipoEstaBloqueado($equipo)) {
            return;
        }

        if ($cerrada) {
            $sigueActiva = static::asignacionActivaDelEquipo($serviceTag) !== null;

            if (! $sigueActiva) {
                $equipo->update(['estado' => EstadoEquipo::SinAsignar]);
            }

            return;
        }

        $equipo->update(['estado' => EstadoEquipo::Asignado]);
    }
}
