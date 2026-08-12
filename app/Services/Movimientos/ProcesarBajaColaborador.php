<?php

namespace App\Services\Movimientos;

use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Models\AccesoDaza;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\AsigCelular;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\AzureAd;
use App\Models\LicenciaUsuario;
use App\Models\Movimiento;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Services\SincronizarAsignacionEquipo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Da de baja todo lo que un colaborador tiene asignado: desactiva sus accesos
 * y cierra con `fecha_fin` sus asignaciones de equipo, celular y cargador.
 *
 * La cascada es unidireccional: un Alta posterior no reactiva nada, y eliminar
 * el movimiento de Baja tampoco la revierte. Es idempotente, así que puede
 * ejecutarse más de una vez sobre el mismo colaborador sin efectos adicionales.
 */
class ProcesarBajaColaborador
{
    /**
     * Modelos de acceso con columna `estado` booleana y FK directa al usuario.
     *
     * @var list<class-string<Model>>
     */
    private const MODELOS_CON_ESTADO = [
        SoftwareUsuario::class,
        LicenciaUsuario::class,
        AccesoSharepoint::class,
        AccesoSistema::class,
        AccesoDaza::class,
        AccesoInternet::class,
        AzureAd::class,
        AccUdCompartida::class,
    ];

    public function __construct(private SincronizarAsignacionEquipo $sincronizador) {}

    public function paraMovimiento(Movimiento $movimiento): ResumenBajaColaborador
    {
        if ($movimiento->tipo !== 'Baja' || blank($movimiento->usuario_id)) {
            return new ResumenBajaColaborador;
        }

        return $this->ejecutar($movimiento->usuario_id, $movimiento->fecha ?? now());
    }

    public function ejecutar(string $usuarioId, CarbonInterface $fecha): ResumenBajaColaborador
    {
        $fechaBaja = CarbonImmutable::parse($fecha)->startOfDay();

        return DB::transaction(function () use ($usuarioId, $fechaBaja): ResumenBajaColaborador {
            $accesos = $this->desactivarAccesosVirtuales($usuarioId);

            foreach (self::MODELOS_CON_ESTADO as $modelo) {
                $accesos += $this->desactivarPorUsuario($modelo, $usuarioId);
            }

            $this->cerrarAccesosSistema($usuarioId, $fechaBaja);

            $equipos = $this->cerrarAsignacionesEquipo($usuarioId, $fechaBaja);

            return new ResumenBajaColaborador(
                accesosDesactivados: $accesos,
                asignacionesEquipoCerradas: $equipos['equipos'],
                asignacionesCelularCerradas: $this->cerrarAsignacionesCelular($usuarioId, $fechaBaja),
                asignacionesCargadorCerradas: $equipos['cargadores'],
                excepcionesSoftwareCerradas: $this->cerrarExcepcionesSoftware($usuarioId, $fechaBaja),
            );
        });
    }

    /**
     * Se usa `Builder::update()` en vez de iterar por tres razones:
     * evita el N+1; no dispara los eventos de modelo, así que el trait
     * WarnsOnInactiveCatalogRelations no emite una notificación por fila;
     * y el filtro `estado = true` hace la operación idempotente.
     *
     * Contrapartida: si algún día estos modelos ganan un hook `updating`,
     * la cascada lo saltará.
     *
     * @param  class-string<Model>  $modelo
     */
    private function desactivarPorUsuario(string $modelo, string $usuarioId): int
    {
        return $modelo::query()
            ->where('usuario_id', $usuarioId)
            ->where('estado', true)
            ->update(['estado' => false]);
    }

    /**
     * Los accesos virtuales (VM + VPN) cuelgan del acceso a internet, no del
     * usuario, así que hay que llegar a ellos por subconsulta.
     */
    private function desactivarAccesosVirtuales(string $usuarioId): int
    {
        return AccesoVirtual::query()
            ->whereIn('internet_id', AccesoInternet::query()
                ->where('usuario_id', $usuarioId)
                ->select('id'))
            ->where('estado', true)
            ->update(['estado' => false]);
    }

    /**
     * `accesos_sistemas` es el único acceso con par fecha_inicio/fecha_fin:
     * además de desactivarlo hay que sellar la fecha de cierre. Se itera
     * porque el clamp contra `fecha_inicio` es por fila.
     */
    private function cerrarAccesosSistema(string $usuarioId, CarbonImmutable $fechaBaja): void
    {
        AccesoSistema::query()
            ->where('usuario_id', $usuarioId)
            ->whereNull('fecha_fin')
            ->get()
            ->each(function (AccesoSistema $acceso) use ($fechaBaja): void {
                $acceso->updateQuietly(['fecha_fin' => $this->fechaCierre($fechaBaja, $acceso->fecha_inicio)]);
            });
    }

    /**
     * Se itera porque cada equipo debe pasar por SincronizarAsignacionEquipo,
     * que respeta los estados Mantenimiento / Resguardo / Baja del equipo.
     *
     * @return array{equipos: int, cargadores: int}
     */
    private function cerrarAsignacionesEquipo(string $usuarioId, CarbonImmutable $fechaBaja): array
    {
        $asignaciones = AsignacionEquipo::query()
            ->where('usuario_id', $usuarioId)
            ->whereNull('fecha_fin')
            ->with('equipo')
            ->get();

        $serviceTags = [];

        foreach ($asignaciones as $asignacion) {
            $asignacion->update(['fecha_fin' => $this->fechaCierre($fechaBaja, $asignacion->fecha_inicio)]);
            $this->sincronizador->sincronizarEstadoEquipo($asignacion);
            $serviceTags[] = $asignacion->service_tag;
        }

        return [
            'equipos' => $asignaciones->count(),
            'cargadores' => $this->cerrarCargadoresDeEquipos($serviceTags, $fechaBaja),
        ];
    }

    /**
     * Al liberar el equipo también se devuelve su cargador al inventario.
     * Cerrar `fecha_fin` libera el índice único parcial de asignación activa.
     *
     * @param  list<string>  $serviceTags
     */
    private function cerrarCargadoresDeEquipos(array $serviceTags, CarbonImmutable $fechaBaja): int
    {
        if ($serviceTags === []) {
            return 0;
        }

        $asignaciones = AsignacionCargador::query()
            ->whereIn('service_tag', $serviceTags)
            ->whereNull('fecha_fin')
            ->get();

        foreach ($asignaciones as $asignacion) {
            $asignacion->update(['fecha_fin' => $this->fechaCierre($fechaBaja, $asignacion->fecha_inicio)]);
            AsignacionCargadorWorkflow::actualizarEstadoCargador($asignacion->serial_cargador, null, cerrada: true);
        }

        return $asignaciones->count();
    }

    /**
     * El cargador de celular pertenece al celular, no al usuario, así que
     * `asig_cargador_cel` queda deliberadamente fuera de la cascada.
     */
    private function cerrarAsignacionesCelular(string $usuarioId, CarbonImmutable $fechaBaja): int
    {
        $asignaciones = AsigCelular::query()
            ->where('usuario_id', $usuarioId)
            ->whereNull('fecha_fin')
            ->get();

        foreach ($asignaciones as $asignacion) {
            $asignacion->update(['fecha_fin' => $this->fechaCierre($fechaBaja, $asignacion->fecha_ini)]);
        }

        return $asignaciones->count();
    }

    /**
     * Una excepción de software es un permiso concedido a la persona:
     * dejarla abierta tras la baja es justo el riesgo que esto cierra.
     */
    private function cerrarExcepcionesSoftware(string $usuarioId, CarbonImmutable $fechaBaja): int
    {
        $excepciones = SoftwareExc::query()
            ->where('usuario_id', $usuarioId)
            ->whereNull('fecha_fin')
            ->get();

        foreach ($excepciones as $excepcion) {
            $excepcion->update(['fecha_fin' => $this->fechaCierre($fechaBaja, $excepcion->fecha_inicio)]);
        }

        return $excepciones->count();
    }

    /**
     * fecha_fin = max(fecha_baja, fecha_inicio). La validación del movimiento
     * solo garantiza que la Baja no sea anterior al Alta, no a cada asignación,
     * así que sin el clamp una asignación registrada con fecha futura quedaría
     * con un rango invertido.
     */
    private function fechaCierre(CarbonImmutable $fechaBaja, ?CarbonInterface $fechaInicio): CarbonImmutable
    {
        if ($fechaInicio === null) {
            return $fechaBaja;
        }

        return $fechaBaja->lt($fechaInicio)
            ? CarbonImmutable::parse($fechaInicio)
            : $fechaBaja;
    }
}
