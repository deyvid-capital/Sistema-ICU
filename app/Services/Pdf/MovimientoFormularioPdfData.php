<?php

namespace App\Services\Pdf;

use App\Enums\MovimientoFormularioPdfTipo;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccUdCompartida;
use App\Models\AsignacionEquipo;
use App\Models\AzureAd;
use App\Models\LicenciaUsuario;
use App\Models\Movimiento;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MovimientoFormularioPdfData
{
    /**
     * Keywords (lowercase) → FormAltaBaja software field.
     *
     * @var array<string, list<string>>
     */
    private const SOFTWARE_FIELD_KEYWORDS = [
        'antivirus' => ['forticlient', 'defender', 'antivirus', 'kaspersky', 'norton'],
        'colaboracion' => ['teams', 'slack', 'colaboracion'],
        'escritorio_remoto' => ['remote desktop', 'rdp', 'escritorio remoto'],
        'mensajeria_instantanea' => ['whatsapp', 'telegram', 'mensajeria'],
        'pdf' => ['acrobat', 'pdfsam', 'adobe acrobat', 'foxit'],
        'vpn' => ['vpn'],
        'navegadores' => ['chrome', 'firefox', 'edge', 'brave', 'navegador'],
        'software_vpn' => ['forticlient vpn', 'openvpn', 'wireguard'],
        'soporte_remoto' => ['anydesk', 'teamviewer', 'rustdesk'],
        'impresora_scaner' => ['canon', 'epson', 'impresora', 'scaner', 'scanner', 'print'],
        'compresion' => ['winrar', '7-zip', '7zip', 'winzip', 'compres'],
        'software_escaner' => ['naps', 'scangear', 'epson scan', 'color network'],
    ];

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

        $usuario = $movimiento->usuario;
        $fechas = $this->resolveFechas($movimiento);

        return [
            'tipo' => $this->text($movimiento->tipo),
            'fecha_alta' => $fechas['fecha_alta'],
            'fecha_baja' => $fechas['fecha_baja'],
            'usuario' => $this->mapUsuario($movimiento, $usuario),
            'equipo' => $this->mapEquipo($usuario),
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
    private function mapEquipo(?User $usuario): array
    {
        /** @var AsignacionEquipo|null $asignacion */
        $asignacion = $usuario?->asignacionesEquipos
            ?->first(fn (AsignacionEquipo $item): bool => $item->fecha_fin === null);

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
        $softwareNombres = $this->collectSoftwareNombres($usuario);

        return [
            'sistemas' => $this->joinList(
                $usuario?->accesosSistemas
                    ?->filter(fn (AccesoSistema $acceso): bool => (bool) $acceso->estado)
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
                    ?->filter(fn (LicenciaUsuario $item): bool => (bool) $item->estado)
                    ->map(fn (LicenciaUsuario $item): ?string => $item->licencia?->nombre_licencia)
                    ->filter()
                    ->values()
                    ->all() ?? []
            ),
            'sitio_sharepoint' => $this->joinList(
                $usuario?->accesosSharepoint
                    ?->filter(fn (AccesoSharepoint $acceso): bool => (bool) $acceso->estado)
                    ->map(fn (AccesoSharepoint $acceso): ?string => $acceso->sitio?->nombre_sitio)
                    ->filter()
                    ->values()
                    ->all() ?? []
            ),
            'antivirus' => $this->softwareField($softwareNombres, 'antivirus'),
            'internet' => $this->mapInternet($usuario),
            'colaboracion' => $this->softwareField($softwareNombres, 'colaboracion'),
            'escritorio_remoto' => $this->softwareField($softwareNombres, 'escritorio_remoto'),
            'mensajeria_instantanea' => $this->softwareField($softwareNombres, 'mensajeria_instantanea'),
            'unidad_compartida' => $this->joinList(
                $usuario?->unidadesCompartidas
                    ?->filter(fn (AccUdCompartida $item): bool => (bool) $item->estado)
                    ->map(fn (AccUdCompartida $item): ?string => $item->unidad_compartida)
                    ->filter()
                    ->values()
                    ->all() ?? []
            ),
            'pdf' => $this->softwareField($softwareNombres, 'pdf'),
            'vpn' => $this->softwareField($softwareNombres, 'vpn'),
            'navegadores' => $this->softwareField($softwareNombres, 'navegadores'),
            'software_vpn' => $this->softwareField($softwareNombres, 'software_vpn'),
            'soporte_remoto' => $this->softwareField($softwareNombres, 'soporte_remoto'),
            'impresora_scaner' => $this->softwareField($softwareNombres, 'impresora_scaner'),
            'compresion' => $this->softwareField($softwareNombres, 'compresion'),
            'software_escaner' => $this->softwareField($softwareNombres, 'software_escaner'),
            'autenticacion_multifactor' => $this->mapMfa($usuario),
        ];
    }

    private function mapInternet(?User $usuario): string
    {
        $tieneInternet = $usuario?->accesosInternet
            ?->contains(fn (AccesoInternet $acceso): bool => (bool) $acceso->estado);

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
            ?->first(fn (AzureAd $item): bool => (bool) $item->estado);

        if ($azure === null) {
            return '-';
        }

        return $azure->mfa ? 'Microsoft Authenticator' : '-';
    }

    /**
     * @return list<string>
     */
    private function collectSoftwareNombres(?User $usuario): array
    {
        if ($usuario === null) {
            return [];
        }

        $fromUsuarios = $usuario->softwareUsuarios
            ->filter(fn (SoftwareUsuario $item): bool => (bool) $item->estado)
            ->map(fn (SoftwareUsuario $item): ?string => $item->software?->nombre_software);

        $fromExcepciones = $usuario->softwareExcepciones
            ->map(fn (SoftwareExc $item): ?string => $item->software?->nombre_software);

        $fromArea = $usuario->area?->softwareAreas
            ?->map(fn ($item): ?string => $item->software?->nombre_software) ?? collect();

        return collect()
            ->merge($fromUsuarios)
            ->merge($fromExcepciones)
            ->merge($fromArea)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $nombres
     */
    private function softwareField(array $nombres, string $field): string
    {
        $keywords = self::SOFTWARE_FIELD_KEYWORDS[$field] ?? [];

        $matches = collect($nombres)
            ->filter(function (string $nombre) use ($keywords): bool {
                $haystack = Str::lower($nombre);

                foreach ($keywords as $keyword) {
                    if (str_contains($haystack, Str::lower($keyword))) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();

        return $this->joinList($matches);
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
