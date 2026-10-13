<?php

namespace App\Services\Pdf;

use App\Enums\CategoriasSoftware;
use App\Enums\EstadoAprobacionExcepcion;
use App\Enums\MovimientoFormularioPdfTipo;
use App\Models\AccesoDaza;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\AsignacionEquipo;
use App\Models\AzureAd;
use App\Models\LicenciaUsuario;
use App\Models\Movimiento;
use App\Models\Software;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MovimientoFormularioPdfData
{
    /**
     * En una Baja los accesos y asignaciones ya fueron desactivados en cascada,
     * así que el formulario debe listar también lo inactivo: de lo contrario el
     * documento saldría vacío justo cuando debe dejar constancia de lo retirado.
     */
    private bool $incluirInactivos = false;

    /**
     * @return array{documento: array<string, mixed>}
     */
    public function for(Model $record, MovimientoFormularioPdfTipo $tipo = MovimientoFormularioPdfTipo::AltaBaja): array
    {
        return ['documento' => $this->fromMovimiento($this->requireMovimiento($record))];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromMovimiento(Movimiento $movimiento): array
    {
        $movimiento->loadMissing([
            'usuario.area',
            'usuario.cargo',
            'usuario.accesosSistemas.sistema',
            'usuario.accesosSharepoint.sitio',
            'usuario.accesosInternet',
            'usuario.accesosDaza.fondo',
            'usuario.accesosVirtual.virtual',
            'usuario.accesosVirtual.recursoVpn',
            'usuario.licenciasUsuarios.licencia',
            'usuario.unidadesCompartidas',
            'usuario.azureAd',
            'usuario.softwareUsuarios.software',
            'usuario.softwareExcepciones.software',
            'usuario.area.softwareAreas.software',
            'usuario.asignacionesEquipos.equipo.modelo',
            'usuario.asignacionesEquipos.entrega',
            'usuario.asignacionesEquipos.recepcion',
            'cargo',
        ]);

        $this->incluirInactivos = $movimiento->tipo === 'Baja';

        $usuario = $movimiento->usuario;
        $fechas = $this->resolveFechas($movimiento);
        $asignacionEquipo = $this->asignacionEquipoPrincipal($usuario);

        return [
            'tipo' => $this->text($movimiento->tipo),
            'fecha_alta' => $fechas['fecha_alta'],
            'fecha_baja' => $fechas['fecha_baja'],
            'usuario' => $this->mapUsuario($movimiento, $usuario),
            'equipo' => $this->mapEquipo($asignacionEquipo),
            'software' => $this->mapSoftware($usuario),
        ];
    }

    /**
     * @return array{fecha_alta: string, fecha_baja: string}
     */
    private function resolveFechas(Movimiento $movimiento): array
    {
        $fechaMovimiento = $movimiento->fecha?->format('d/m/Y');

        if ($movimiento->tipo === 'Alta') {
            $bajaPosterior = Movimiento::query()
                ->where('usuario_id', $movimiento->usuario_id)
                ->where('tipo', 'Baja')
                ->where('fecha', '>=', $movimiento->fecha)
                ->where('id', '!=', $movimiento->id)
                ->orderBy('fecha')
                ->orderBy('id')
                ->first();

            return [
                'fecha_alta' => $this->text($fechaMovimiento),
                'fecha_baja' => $this->text($bajaPosterior?->fecha?->format('d/m/Y')),
            ];
        }

        $altaPrevia = Movimiento::query()
            ->where('usuario_id', $movimiento->usuario_id)
            ->where('tipo', 'Alta')
            ->where('fecha', '<=', $movimiento->fecha)
            ->where('id', '!=', $movimiento->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        return [
            'fecha_alta' => $this->text($altaPrevia?->fecha?->format('d/m/Y')),
            'fecha_baja' => $this->text($fechaMovimiento),
        ];
    }

    /**
     * @return array{nombres: string, apellido: string, carnet: string, area: string, cargo: string, email: string}
     */
    private function mapUsuario(Movimiento $movimiento, ?User $usuario): array
    {
        return [
            'nombres' => $this->text($usuario?->nombres),
            'apellido' => $this->text($usuario?->apellidos),
            'carnet' => $this->text($usuario?->carnet),
            'area' => $this->text($usuario?->area?->area),
            'cargo' => $this->text($movimiento->cargo?->cargo ?? $usuario?->cargo?->cargo),
            'email' => $this->text($usuario?->email),
        ];
    }

    /**
     * @return array{
     *     modelo: string,
     *     nombre: string,
     *     service_tag: string,
     *     fecha_entrega: string,
     *     fecha_recepcion: string,
     *     sistema_operativo: string
     * }
     */
    private function asignacionEquipoPrincipal(?User $usuario): ?AsignacionEquipo
    {
        $asignaciones = $usuario?->asignacionesEquipos;

        $asignacion = $asignaciones?->first(fn (AsignacionEquipo $item): bool => $item->fecha_fin === null);

        if ($asignacion === null && $this->incluirInactivos) {
            return $asignaciones?->sortByDesc('fecha_inicio')->first();
        }

        return $asignacion;
    }

    private function mapEquipo(?AsignacionEquipo $asignacion): array
    {
        $equipo = $asignacion?->equipo;

        return [
            'modelo' => $this->text($equipo?->modelo?->nombre),
            'nombre' => $this->text($equipo?->nombre_equipo),
            'service_tag' => $this->text($equipo?->service_tag ?? $asignacion?->service_tag),
            'fecha_entrega' => $this->text($asignacion?->entrega?->fecha?->format('d/m/Y')),
            'fecha_recepcion' => $this->text($asignacion?->recepcion?->fecha?->format('d/m/Y')),
            'sistema_operativo' => $this->text($equipo?->so),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mapSoftware(?User $usuario): array
    {
        $porCategoria = $this->softwarePorCategoria($usuario);

        return [
            'sistemas' => $this->joinList(
                $usuario?->accesosSistemas
                    ?->filter(fn (AccesoSistema $acceso): bool => $this->vigente($acceso->estado))
                    ->map(function (AccesoSistema $acceso): ?string {
                        $nombre = $acceso->sistema?->nombre_sistema;

                        if (blank($nombre)) {
                            return null;
                        }

                        $nivel = filled($acceso->nivel_acceso) ? ' - '.$acceso->nivel_acceso : '';

                        return $nombre.$nivel;
                    })
                    ->filter()
                    ->values()
                    ->all() ?? []
            ),
            'microsoft' => $this->joinList(
                $usuario?->licenciasUsuarios
                    ?->filter(fn (LicenciaUsuario $item): bool => $this->vigente($item->estado))
                    ->map(fn (LicenciaUsuario $item): ?string => $item->licencia?->nombre_licencia)
                    ->filter()
                    ->values()
                    ->all() ?? []
            ),
            'sitio_sharepoint' => $this->joinList(
                $usuario?->accesosSharepoint
                    ?->filter(fn (AccesoSharepoint $acceso): bool => $this->vigente($acceso->estado))
                    ->map(fn (AccesoSharepoint $acceso): ?string => $acceso->sitio?->nombre_sitio)
                    ->filter()
                    ->values()
                    ->all() ?? []
            ),
            'antivirus' => $this->nombresCategoria($porCategoria, CategoriasSoftware::Antivirus),
            'internet' => $this->mapInternet($usuario),
            'excepciones' => $this->mapExcepciones($usuario),
            'escritorio_remoto' => $this->mapEscritorioRemoto($usuario),
            'daza' => $this->mapDaza($usuario),
            'unidad_compartida' => $this->joinList(
                $usuario?->unidadesCompartidas
                    ?->filter(fn (AccUdCompartida $item): bool => $this->vigente($item->estado))
                    ->map(fn (AccUdCompartida $item): ?string => $item->unidad_compartida)
                    ->filter()
                    ->values()
                    ->all() ?? []
            ),
            'pdf' => $this->nombresCategoria($porCategoria, CategoriasSoftware::PDF),
            'vpn' => $this->mapVpn($usuario),
            'navegadores' => $this->nombresCategoria($porCategoria, CategoriasSoftware::Navegadores),
            'software_vpn' => $this->nombresCategoria($porCategoria, CategoriasSoftware::SoftwareVPN, CategoriasSoftware::VPN),
            'soporte_remoto' => $this->nombresCategoria($porCategoria, CategoriasSoftware::SoporteRemoto),
            'impresora_scaner' => $this->nombresCategoria($porCategoria, CategoriasSoftware::Impresora, CategoriasSoftware::Scanner),
            'compresion' => $this->nombresCategoria($porCategoria, CategoriasSoftware::Compresion),
            'software_escaner' => $this->nombresCategoria($porCategoria, CategoriasSoftware::SoftwareEscaner),
            'autenticacion_multifactor' => $this->joinList([
                $this->mapMfa($usuario) === '-' ? null : $this->mapMfa($usuario),
                ...$this->nombresDe($porCategoria, CategoriasSoftware::AutenticacionMultifactor),
            ]),
        ];
    }

    private function mapInternet(?User $usuario): string
    {
        $tieneInternet = $usuario?->accesosInternet
            ?->contains(fn (AccesoInternet $acceso): bool => $this->vigente($acceso->estado));

        if (! $tieneInternet) {
            return '-';
        }

        $area = $usuario?->area?->area;

        if (blank($area)) {
            return '-';
        }

        return $area.' ('.$this->siglasArea($area).')';
    }

    private function mapMfa(?User $usuario): string
    {
        /** @var AzureAd|null $azure */
        $azure = $usuario?->azureAd
            ?->first(fn (AzureAd $item): bool => $this->vigente($item->estado));

        if ($azure === null) {
            return '-';
        }

        return $azure->mfa ? 'Microsoft Authenticator' : '-';
    }

    private function mapEscritorioRemoto(?User $usuario): string
    {
        return $this->joinList(
            $usuario?->accesosVirtual
                ?->filter(fn (AccesoVirtual $acceso): bool => $this->vigente($acceso->estado))
                ->map(fn (AccesoVirtual $acceso): ?string => $acceso->virtual?->maquina_virtual)
                ->filter()
                ->unique()
                ->values()
                ->all() ?? []
        );
    }

    private function mapVpn(?User $usuario): string
    {
        return $this->joinList(
            $usuario?->accesosVirtual
                ?->filter(fn (AccesoVirtual $acceso): bool => $this->vigente($acceso->estado) && filled($acceso->vpn_id))
                ->map(fn (AccesoVirtual $acceso): ?string => $acceso->recursoVpn?->nombre_vpn)
                ->filter()
                ->unique()
                ->values()
                ->all() ?? []
        );
    }

    private function mapDaza(?User $usuario): string
    {
        return $this->joinList(
            $usuario?->accesosDaza
                ?->filter(fn (AccesoDaza $acceso): bool => $this->vigente($acceso->estado))
                ->map(function (AccesoDaza $acceso): string {
                    $fondo = $acceso->fondo?->nombre_completo ?? $acceso->fondo?->codigo;
                    $partes = array_filter([
                        $acceso->usuario_daza,
                        $acceso->nivel_acceso,
                        $fondo,
                        $acceso->ambiente,
                    ], fn (?string $parte): bool => filled($parte));

                    return implode(' - ', $partes);
                })
                ->filter()
                ->values()
                ->all() ?? []
        );
    }

    private function mapExcepciones(?User $usuario): string
    {
        return $this->joinList(
            $usuario?->softwareExcepciones
                ?->map(function (SoftwareExc $excepcion): ?string {
                    $nombre = $excepcion->software?->nombre_software;

                    if (blank($nombre)) {
                        return null;
                    }

                    $estado = $excepcion->estado_aprobacion?->getLabel() ?? 'Pendiente';

                    return $nombre.' ('.$estado.')';
                })
                ->filter()
                ->values()
                ->all() ?? []
        );
    }

    /**
     * Software asignado al usuario, el de su área y las excepciones aprobadas,
     * agrupado por el valor de CategoriasSoftware.
     *
     * @return Collection<string, Collection<int, Software>>
     */
    private function softwarePorCategoria(?User $usuario): Collection
    {
        if ($usuario === null) {
            return collect();
        }

        $fromUsuarios = $usuario->softwareUsuarios
            ->filter(fn (SoftwareUsuario $item): bool => $this->vigente($item->estado))
            ->map(fn (SoftwareUsuario $item): ?Software => $item->software);

        $fromExcepciones = $usuario->softwareExcepciones
            ->filter(fn (SoftwareExc $item): bool => $item->estado_aprobacion === EstadoAprobacionExcepcion::Aprobada)
            ->filter(fn (SoftwareExc $item): bool => $this->incluirInactivos || $item->fecha_fin === null)
            ->map(fn (SoftwareExc $item): ?Software => $item->software);

        $fromArea = $usuario->area?->softwareAreas
            ?->map(fn ($item): ?Software => $item->software) ?? collect();

        return collect()
            ->merge($fromUsuarios)
            ->merge($fromExcepciones)
            ->merge($fromArea)
            ->filter()
            ->unique(fn (Software $software): int => (int) $software->getKey())
            ->groupBy(fn (Software $software): string => (string) ($software->categoria ?? ''));
    }

    /**
     * @param  Collection<string, Collection<int, Software>>  $porCategoria
     */
    private function nombresCategoria(Collection $porCategoria, CategoriasSoftware ...$categorias): string
    {
        return $this->joinList($this->nombresDe($porCategoria, ...$categorias));
    }

    /**
     * @param  Collection<string, Collection<int, Software>>  $porCategoria
     * @return list<string>
     */
    private function nombresDe(Collection $porCategoria, CategoriasSoftware ...$categorias): array
    {
        return collect($categorias)
            ->flatMap(fn (CategoriasSoftware $categoria) => $porCategoria->get($categoria->value, collect()))
            ->map(fn (Software $software): string => (string) $software->nombre_software)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string|null>  $items
     */
    private function joinList(array $items): string
    {
        $filtered = array_values(array_filter($items, fn ($item): bool => filled($item)));

        return $filtered === [] ? '-' : implode(' | ', $filtered);
    }

    private function siglasArea(string $area): string
    {
        $parts = preg_split('/\s+/u', trim($area)) ?: [];

        $siglas = collect($parts)
            ->filter(fn (string $part): bool => mb_strlen($part) > 2 || ctype_upper($part))
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        if ($siglas === '') {
            $siglas = mb_strtoupper(mb_substr(preg_replace('/\s+/u', '', $area) ?? $area, 0, 2));
        }

        return $siglas;
    }

    /**
     * Un registro entra al formulario si está activo, o si estamos imprimiendo
     * una Baja (donde la cascada ya lo desactivó).
     */
    private function vigente(?bool $estado): bool
    {
        return $this->incluirInactivos || (bool) $estado;
    }

    private function text(?string $value): string
    {
        return filled($value) ? $value : '-';
    }

    private function requireMovimiento(Model $record): Movimiento
    {
        if (! $record instanceof Movimiento) {
            throw new \InvalidArgumentException('Se esperaba '.Movimiento::class.'.');
        }

        return $record;
    }
}
