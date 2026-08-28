<?php

namespace App\Services;

use App\Enums\EstadoEquipo;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoWorkflow;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cambia el estado de un equipo cerrando, si procede, la asignación vigente.
 *
 * «Asignado» es el único estado derivado de las asignaciones; cualquier otro
 * estado elegido manualmente es excluyente con una asignación abierta, así que
 * al aplicarlo se cierra la asignación activa con la fecha del día.
 */
class CambiarEstadoEquipo
{
    /**
     * Asignación abierta del equipo, con usuario y área precargados.
     */
    public function asignacionActiva(Equipo $equipo): ?AsignacionEquipo
    {
        return AsignacionEquipo::query()
            ->with('usuario.area')
            ->where('service_tag', $equipo->getKey())
            ->whereNull('fecha_fin')
            ->latest('fecha_inicio')
            ->first();
    }

    /**
     * Indica si aplicar el nuevo estado obliga a cerrar una asignación abierta.
     */
    public function requiereConfirmacion(Equipo $equipo, EstadoEquipo $nuevo): bool
    {
        if (! $nuevo->esSeleccionableManualmente()) {
            return false;
        }

        return $this->asignacionActiva($equipo) !== null;
    }

    /**
     * Texto «Nombre — Área» para los avisos de confirmación.
     */
    public static function descripcionAsignacion(AsignacionEquipo $asignacion): string
    {
        $usuario = $asignacion->usuario;
        $nombre = $usuario?->name ?? (string) $asignacion->usuario_id;
        $area = $usuario?->area?->area;

        return blank($area) ? $nombre : "{$nombre} — {$area}";
    }

    /**
     * Aplica el nuevo estado y devuelve la asignación cerrada, si hubo alguna.
     */
    public function ejecutar(Equipo $equipo, EstadoEquipo $nuevo): ?AsignacionEquipo
    {
        if ($equipo->trashed()) {
            throw new RuntimeException('No se puede cambiar el estado de un equipo que está en la papelera.');
        }

        if (! $nuevo->esSeleccionableManualmente()) {
            throw new RuntimeException("El estado «{$nuevo->getLabel()}» se asigna automáticamente al crear una asignación y no puede elegirse a mano.");
        }

        return DB::transaction(function () use ($equipo, $nuevo): ?AsignacionEquipo {
            $cerrada = $this->cerrarAsignacionActiva($equipo, $nuevo);

            $equipo->update(['estado' => $nuevo]);

            return $cerrada;
        });
    }

    private function cerrarAsignacionActiva(Equipo $equipo, EstadoEquipo $nuevo): ?AsignacionEquipo
    {
        $activa = $this->asignacionActiva($equipo);

        if ($activa === null) {
            return null;
        }

        $fechaFin = $this->fechaCierre($activa->fecha_inicio);

        $activa->update([
            'fecha_fin' => $fechaFin,
            'observaciones' => AsignacionEquipoWorkflow::appendObservacion(
                $activa->observaciones,
                AsignacionEquipoWorkflow::notaCierrePorEstado($nuevo, $fechaFin),
            ),
        ]);

        return $activa;
    }

    /**
     * Cierra en la fecha de hoy, nunca antes del inicio de la asignación.
     */
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
