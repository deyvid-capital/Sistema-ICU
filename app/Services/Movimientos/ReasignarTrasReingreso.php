<?php

namespace App\Services\Movimientos;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Models\AccesoSistema;
use App\Models\AsigCelular;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Movimiento;
use App\Models\SoftwareExc;
use App\Models\User;
use App\Services\SincronizarAsignacionEquipo;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Devuelve a un colaborador que reingresa lo que su última Baja le quitó.
 *
 * No es automático: la Baja sigue siendo unidireccional y esto solo corre
 * cuando alguien lo pide desde la ficha del usuario (acción «Reasignar todo»).
 *
 * Qué devuelve, sin filtrar:
 * - Todos sus accesos inactivos vuelven a estar activos.
 * - Se reabren las asignaciones de equipo, cargador y celular, y las
 *   excepciones de software, que se cerraron en la fecha de la Baja.
 *
 * Único límite: lo que hoy tiene otra persona, o no está disponible, no se
 * toca y se informa en el resumen.
 */
class ReasignarTrasReingreso
{
    public function __construct(private SincronizarAsignacionEquipo $sincronizador) {}

    /**
     * Está de Alta y antes tuvo una Baja.
     */
    public function esReingreso(User $usuario): bool
    {
        return $usuario->ultimoMovimiento()->first()?->tipo === 'Alta'
            && $this->ultimaBaja($usuario) !== null;
    }

    public function ultimaBaja(User $usuario): ?Movimiento
    {
        return Movimiento::query()
            ->where('usuario_id', $usuario->getKey())
            ->where('tipo', 'Baja')
            ->orderByDesc('fecha')
            ->first();
    }

    /**
     * Lo que «Reasignar todo» devolvería ahora mismo, sin cambiar nada.
     */
    public function pendiente(User $usuario): ResumenReasignacion
    {
        return $this->recorrer($usuario, aplicar: false);
    }

    public function ejecutar(User $usuario): ResumenReasignacion
    {
        return DB::transaction(fn (): ResumenReasignacion => $this->recorrer($usuario, aplicar: true));
    }

    private function recorrer(User $usuario, bool $aplicar): ResumenReasignacion
    {
        $baja = $this->esReingreso($usuario) ? $this->ultimaBaja($usuario) : null;

        if ($baja === null || $baja->fecha === null) {
            return new ResumenReasignacion;
        }

        $usuarioId = (string) $usuario->getKey();
        $fecha = $baja->fecha;
        $omitidos = [];

        $accesos = $this->accesos($usuarioId, $fecha, $aplicar);
        [$equipos, $serviceTags] = $this->equipos($usuarioId, $fecha, $aplicar, $omitidos);

        return new ResumenReasignacion(
            accesos: $accesos,
            equipos: $equipos,
            cargadores: $this->cargadores($serviceTags, $fecha, $aplicar, $omitidos),
            celulares: $this->celulares($usuarioId, $fecha, $aplicar, $omitidos),
            excepcionesSoftware: $this->excepcionesSoftware($usuarioId, $fecha, $aplicar),
            omitidos: $omitidos,
        );
    }

    /**
     * Todos los accesos inactivos del usuario, sin distinguir cuáles apagó la Baja.
     */
    private function accesos(string $usuarioId, CarbonInterface $fecha, bool $aplicar): int
    {
        $total = 0;

        foreach (ProcesarBajaColaborador::MODELOS_CON_ESTADO as $modelo) {
            $inactivos = $modelo::query()->where('usuario_id', $usuarioId)->where('estado', false);

            $total += $aplicar ? $inactivos->update(['estado' => true]) : $inactivos->count();
        }

        if ($aplicar) {
            // La Baja selló la fecha de cierre de sus accesos a sistemas.
            AccesoSistema::query()
                ->where('usuario_id', $usuarioId)
                ->whereDate('fecha_fin', $fecha)
                ->get()
                ->each(fn (AccesoSistema $acceso) => $acceso->updateQuietly(['fecha_fin' => null]));
        }

        return $total;
    }

    /**
     * @param  list<string>  $omitidos
     * @return array{0: int, 1: list<string>} cantidad y service tags devueltos
     */
    private function equipos(string $usuarioId, CarbonInterface $fecha, bool $aplicar, array &$omitidos): array
    {
        $serviceTags = [];

        $cerradas = AsignacionEquipo::query()
            ->where('usuario_id', $usuarioId)
            ->whereDate('fecha_fin', $fecha)
            ->with('equipo')
            ->get();

        foreach ($cerradas as $asignacion) {
            $equipo = $asignacion->equipo;
            $estado = $equipo?->estado instanceof EstadoEquipo
                ? $equipo->estado
                : EstadoEquipo::tryFrom((string) $equipo?->estado);

            $libre = $equipo !== null
                && ! $equipo->trashed()
                && ($estado?->isDisponibleParaAsignacion() ?? false)
                && ! in_array($asignacion->service_tag, $serviceTags, true)
                && ! AsignacionEquipo::query()->where('service_tag', $asignacion->service_tag)->whereNull('fecha_fin')->exists();

            if (! $libre) {
                $omitidos[] = "equipo {$asignacion->service_tag}";

                continue;
            }

            if ($aplicar) {
                $asignacion->update(['fecha_fin' => null]);
                $this->sincronizador->sincronizarEstadoEquipo($asignacion);
            }

            $serviceTags[] = $asignacion->service_tag;
        }

        return [count($serviceTags), $serviceTags];
    }

    /**
     * Los cargadores que la Baja devolvió al inventario junto con sus equipos.
     *
     * @param  list<string>  $serviceTags
     * @param  list<string>  $omitidos
     */
    private function cargadores(array $serviceTags, CarbonInterface $fecha, bool $aplicar, array &$omitidos): int
    {
        if ($serviceTags === []) {
            return 0;
        }

        $devueltos = [];

        $cerradas = AsignacionCargador::query()
            ->whereIn('service_tag', $serviceTags)
            ->whereDate('fecha_fin', $fecha)
            ->with('cargador')
            ->get();

        foreach ($cerradas as $asignacion) {
            $cargador = $asignacion->cargador;

            $libre = $cargador !== null
                && $cargador->estado === EstadoCargador::Disponible
                && ! in_array($asignacion->serial_cargador, $devueltos, true)
                && ! AsignacionCargador::query()->where('serial_cargador', $asignacion->serial_cargador)->whereNull('fecha_fin')->exists()
                && ! AsignacionCargador::query()->where('service_tag', $asignacion->service_tag)->whereNull('fecha_fin')->exists();

            if (! $libre) {
                $omitidos[] = "cargador {$asignacion->serial_cargador}";

                continue;
            }

            if ($aplicar) {
                $asignacion->update(['fecha_fin' => null]);
                AsignacionCargadorWorkflow::actualizarEstadoCargador($asignacion->serial_cargador);
            }

            $devueltos[] = $asignacion->serial_cargador;
        }

        return count($devueltos);
    }

    /**
     * @param  list<string>  $omitidos
     */
    private function celulares(string $usuarioId, CarbonInterface $fecha, bool $aplicar, array &$omitidos): int
    {
        $devueltos = [];

        $cerradas = AsigCelular::query()
            ->where('usuario_id', $usuarioId)
            ->whereDate('fecha_fin', $fecha)
            ->with('celular')
            ->get();

        foreach ($cerradas as $asignacion) {
            $libre = $asignacion->celular !== null
                && ! $asignacion->celular->trashed()
                && ! in_array($asignacion->celular_id, $devueltos, true)
                && ! AsigCelular::query()->where('celular_id', $asignacion->celular_id)->whereNull('fecha_fin')->exists();

            if (! $libre) {
                $omitidos[] = "celular {$asignacion->celular_id}";

                continue;
            }

            if ($aplicar) {
                $asignacion->update(['fecha_fin' => null]);
            }

            $devueltos[] = $asignacion->celular_id;
        }

        return count($devueltos);
    }

    private function excepcionesSoftware(string $usuarioId, CarbonInterface $fecha, bool $aplicar): int
    {
        $cerradas = SoftwareExc::query()
            ->where('usuario_id', $usuarioId)
            ->whereDate('fecha_fin', $fecha)
            ->get();

        if ($aplicar) {
            $cerradas->each(fn (SoftwareExc $excepcion) => $excepcion->update(['fecha_fin' => null]));
        }

        return $cerradas->count();
    }
}
