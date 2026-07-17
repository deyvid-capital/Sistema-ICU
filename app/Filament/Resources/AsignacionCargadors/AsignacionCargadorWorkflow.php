<?php

namespace App\Filament\Resources\AsignacionCargadors;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Enums\ModoAsignacionCargador;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use App\Models\Equipo;
use Illuminate\Support\Carbon;

/**
 * Lógica de negocio de asignación de cargadores (temporal / reasignación).
 * Pensado para llamarse desde Create/Edit o RelationManagers.
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

    /**
     * El equipo origen debe estar libre de usuario:
     * - estado = sin asignar
     * - sin AsignacionEquipo activa (fecha_fin null)
     *
     * @return list<array{titulo: string, cuerpo: string}>
     */
    public static function problemasEquipoOrigenLibre(Equipo $equipo): array
    {
        $problemas = [];

        $estado = $equipo->estado instanceof EstadoEquipo
            ? $equipo->estado
            : EstadoEquipo::tryFrom((string) $equipo->estado);

        if ($estado !== EstadoEquipo::SinAsignar) {
            $label = $estado?->getLabel() ?? (string) $equipo->estado;
            $problemas[] = [
                'titulo' => 'El equipo origen no está «Sin asignar»',
                'cuerpo' => "El equipo {$equipo->service_tag} ({$equipo->nombre_equipo}) tiene estado «{$label}». Cámbielo a «Sin asignar» antes de mover el cargador.",
            ];
        }

        $asignacionUsuarioActiva = $equipo->asignacionActiva()->exists();

        if ($asignacionUsuarioActiva) {
            $problemas[] = [
                'titulo' => 'El equipo origen aún tiene usuario asignado',
                'cuerpo' => "El equipo {$equipo->service_tag} tiene una asignación de usuario sin fecha de fin. Cierre esa asignación (indique fecha de fin) antes de mover el cargador.",
            ];
        }

        return $problemas;
    }

    public static function notaMovimiento(
        string $equipoOrigen,
        string $equipoDestino,
        ModoAsignacionCargador $modo,
        Carbon|string|null $fecha = null,
    ): string {
        $fechaTexto = Carbon::parse($fecha ?? now())->format('d/m/Y');
        $tipo = $modo === ModoAsignacionCargador::Temporal
            ? 'Asignación temporal'
            : 'Reasignación permanente';

        return "<p><strong>{$tipo}:</strong> del equipo <strong>{$equipoOrigen}</strong> al equipo <strong>{$equipoDestino}</strong> el {$fechaTexto}.</p>";
    }

    /**
     * Cierra la asignación activa del cargador (equipo A) y anota el movimiento.
     * También cierra otra asignación activa del equipo destino si existiera.
     *
     * @return string Nota HTML para agregar a la nueva asignación
     */
    public static function cerrarAsignacionesPreviasYAnotar(
        string $serialCargador,
        string $equipoDestino,
        ModoAsignacionCargador $modo,
        ?int $ignoreAsignacionId = null,
    ): string {
        $fecha = now()->toDateString();
        $nota = '';

        $activaDelCargador = static::asignacionActivaDelCargador($serialCargador, $ignoreAsignacionId);

        if ($activaDelCargador && $activaDelCargador->service_tag !== $equipoDestino) {
            $nota = static::notaMovimiento(
                $activaDelCargador->service_tag,
                $equipoDestino,
                $modo,
                $fecha,
            );

            $activaDelCargador->update([
                'fecha_fin' => $fecha,
                'observaciones' => static::appendObservacion($activaDelCargador->observaciones, $nota),
            ]);
        }

        AsignacionCargador::query()
            ->where('service_tag', $equipoDestino)
            ->whereNull('fecha_fin')
            ->when($ignoreAsignacionId !== null, fn ($q) => $q->whereKeyNot($ignoreAsignacionId))
            ->when(
                $activaDelCargador !== null,
                fn ($q) => $q->whereKeyNot($activaDelCargador->getKey()),
            )
            ->update(['fecha_fin' => $fecha]);

        return $nota;
    }

    public static function appendObservacion(string|array|null $actual, string $nota): string
    {
        if (is_array($actual)) {
            // Documento TipTap vacío o con un <p></p> vacío
            $actual = ''; // o convertir a HTML con RichContentRenderer si quieres conservar lo escrito
        }
    
        $actual = trim((string) ($actual ?? ''));
    
        return $actual === '' ? $nota : $actual.$nota;
    }

    public static function actualizarEstadoCargador(string $serial, ?ModoAsignacionCargador $modo, bool $cerrada = false): void
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

        $cargador->update([
            'estado' => $modo?->toEstadoCargador() ?? EstadoCargador::Asignado,
        ]);
    }

    public static function parseModo(mixed $value): ?ModoAsignacionCargador
    {
        if ($value instanceof ModoAsignacionCargador) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return ModoAsignacionCargador::tryFrom($value);
        }

        return null;
    }
}
