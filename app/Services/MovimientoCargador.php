<?php

namespace App\Services;

use App\Enums\EstadoCargador;
use App\Enums\ModoAsignacionCargador;
use App\Exceptions\EquipoOrigenConUsuarioException;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Presta o cede un cargador que ya está en otro equipo, y cierra ese ciclo
 * cuando llega el cargador comprado.
 */
class MovimientoCargador
{
    public function prestarOCeder(
        string $serialCargador,
        string $equipoDestino,
        ModoAsignacionCargador $modo,
        User $actor,
    ): AsignacionCargador {
        $cargador = Cargador::query()->find($serialCargador);

        if (! $cargador || $this->estado($cargador)?->estaBloqueado()) {
            throw new RuntimeException('El cargador no se puede mover.');
        }

        $activa = AsignacionCargador::query()
            ->where('serial_cargador', $serialCargador)
            ->whereNull('fecha_fin')
            ->first();

        if ($activa === null || $activa->service_tag === $equipoDestino) {
            throw new RuntimeException('Elija un cargador que esté asignado a otro equipo.');
        }

        $origen = Equipo::query()->find($activa->service_tag);
        $asignacionUsuario = $origen?->asignacionActiva()->with('usuario')->first();

        if ($asignacionUsuario instanceof AsignacionEquipo) {
            $this->notificarOrigenConUsuario($actor, $origen, $asignacionUsuario);

            throw new EquipoOrigenConUsuarioException($asignacionUsuario);
        }

        return DB::transaction(function () use ($serialCargador, $equipoDestino, $modo, $cargador, $activa): AsignacionCargador {

            $hoy = now()->toDateString();

            $this->cerrarAsignacionActivaDelEquipo($equipoDestino, $hoy);

            $activa->update(['fecha_fin' => $hoy]);

            $nueva = AsignacionCargador::query()->create([
                'serial_cargador' => $serialCargador,
                'service_tag' => $equipoDestino,
                'service_tag_origen' => $activa->service_tag,
                'fecha_inicio' => $hoy,
            ]);

            $cargador->update(['estado' => $modo->toEstadoCargador()]);

            return $nueva;
        });
    }

    public function registrarComprado(AsignacionCargador $prestamo, string $serialNuevo): void
    {
        DB::transaction(function () use ($prestamo, $serialNuevo): void {
            $prestamo->refresh();

            if ($prestamo->fecha_fin !== null || blank($prestamo->service_tag_origen)) {
                throw new RuntimeException('Esta asignación no tiene un cargador pendiente de reemplazo.');
            }

            $nuevo = Cargador::query()->find($serialNuevo);

            if (! $nuevo || ! $this->estado($nuevo)?->isLibreParaAsignacionDirecta()) {
                throw new RuntimeException('El cargador nuevo debe estar disponible.');
            }

            $origenOcupado = AsignacionCargador::query()
                ->where('service_tag', $prestamo->service_tag_origen)
                ->whereNull('fecha_fin')
                ->exists();

            if ($origenOcupado) {
                throw new RuntimeException('El equipo origen ya tiene un cargador asignado. Ciérrelo antes de registrar el comprado.');
            }

            $prestado = $prestamo->cargador;
            $estado = $prestado ? $this->estado($prestado) : null;
            $hoy = now()->toDateString();

            if ($estado === EstadoCargador::Temporal) {
                $prestamo->update(['fecha_fin' => $hoy]);

                AsignacionCargador::query()->create([
                    'serial_cargador' => $prestamo->serial_cargador,
                    'service_tag' => $prestamo->service_tag_origen,
                    'fecha_inicio' => $hoy,
                ]);

                AsignacionCargador::query()->create([
                    'serial_cargador' => $serialNuevo,
                    'service_tag' => $prestamo->service_tag,
                    'fecha_inicio' => $hoy,
                ]);

                $prestado?->update(['estado' => EstadoCargador::Asignado]);
                $nuevo->update(['estado' => EstadoCargador::Asignado]);

                return;
            }

            if ($estado === EstadoCargador::Reasignacion) {
                $origen = $prestamo->service_tag_origen;

                $prestamo->update(['service_tag_origen' => null]);
                $prestado?->update(['estado' => EstadoCargador::Asignado]);

                AsignacionCargador::query()->create([
                    'serial_cargador' => $serialNuevo,
                    'service_tag' => $origen,
                    'fecha_inicio' => $hoy,
                ]);

                $nuevo->update(['estado' => EstadoCargador::Asignado]);

                return;
            }

            throw new RuntimeException('El cargador no está prestado ni cedido.');
        });
    }

    private function cerrarAsignacionActivaDelEquipo(string $serviceTag, string $fecha): void
    {
        $activa = AsignacionCargador::query()
            ->where('service_tag', $serviceTag)
            ->whereNull('fecha_fin')
            ->first();

        if ($activa === null) {
            return;
        }

        $activa->update(['fecha_fin' => $fecha]);

        $cargador = $activa->cargador;

        if ($cargador && ! $this->estado($cargador)?->estaBloqueado()) {
            $sigueActiva = AsignacionCargador::query()
                ->where('serial_cargador', $activa->serial_cargador)
                ->whereNull('fecha_fin')
                ->exists();

            if (! $sigueActiva) {
                $cargador->update(['estado' => EstadoCargador::Disponible]);
            }
        }
    }

    private function notificarOrigenConUsuario(User $actor, Equipo $equipo, AsignacionEquipo $asignacion): void
    {
        $nombre = $asignacion->usuario?->name ?? 'un usuario';
        $url = AsignacionEquipoResource::getUrl('view', ['record' => $asignacion]);

        $actor->notifyNow(Notification::make()
            ->title('No se puede usar ese cargador')
            ->body("El equipo {$equipo->service_tag} está asignado a {$nombre}. Cierre esa asignación o busque otro cargador.")
            ->warning()
            ->icon('heroicon-o-bell')
            ->actions([
                Action::make('verAsignacion')
                    ->label('Ver asignación')
                    ->url($url),
            ])
            ->toDatabase());
    }

    private function estado(Cargador $cargador): ?EstadoCargador
    {
        return $cargador->estado instanceof EstadoCargador
            ? $cargador->estado
            : EstadoCargador::tryFrom((string) $cargador->estado);
    }
}
