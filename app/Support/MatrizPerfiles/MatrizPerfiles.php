<?php

namespace App\Support\MatrizPerfiles;

use App\Enums\CategoriasSoftware;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\AzureAd;
use App\Models\Cargo;
use App\Models\CategoriaSoftware;
use App\Models\LicenciaUsuario;
use App\Models\Software;
use App\Models\SoftwareCargo;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Support\SiglasArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * Matriz de perfiles: una fila por cargo con los accesos y el software que
 * tienen hoy las personas de ese cargo.
 *
 * - Cada cargo sale una sola vez, aunque lo ocupen varias personas: sus
 *   accesos se unen y los valores repetidos se cuentan una vez.
 * - Solo cuentan las personas que no están de baja y sus asignaciones activas.
 * - Varios valores van en la misma celda separados por " | "; sin valores, "-".
 * - Internet: si la persona tiene acceso activo, el perfil de su área con
 *   siglas, "Inversiones Alternativas (IA)", como en el formulario de alta/baja.
 * - Software: el asignado a la persona y el autorizado para su cargo. Las
 *   excepciones no cuentan porque son personales, no del perfil del cargo. Una
 *   columna por categoría del catálogo con al menos un valor en la matriz:
 *   primero las de CategoriasSoftware, en su orden, y luego las demás.
 *
 * El resto de columnas sigue las reglas del formulario de alta/baja
 * (MovimientoFormularioPdfData): "Sistema - nivel", licencias en Microsoft y
 * MFA de Azure AD en Autenticación multifactor.
 */
class MatrizPerfiles
{
    public const SEPARADOR = ' | ';

    public const VACIO = '-';

    /**
     * Columnas de accesos, en el orden en que salen tras "Cargo".
     *
     * @var array<string, string>
     */
    private const COLUMNAS_DE_ACCESOS = [
        'sistemas' => 'Sistemas',
        'sharepoint' => 'Sitios SharePoint',
        'internet' => 'Internet',
        'escritorio_remoto' => 'Escritorio remoto',
        'unidad_compartida' => 'Unidad compartida',
        'vpn' => 'VPN',
    ];

    /**
     * Categorías que el formulario muestra juntas: el software de categoría
     * "VPN" va con "Software VPN" para no confundirlo con la columna de
     * accesos VPN.
     *
     * @var array<string, string>
     */
    private const CATEGORIA_AGRUPADA_EN = [
        'vpn' => 'software_vpn',
    ];

    /**
     * @var list<array{cargo: string, valores: array<string, list<string>>}>|null
     */
    private ?array $perfiles = null;

    /**
     * @return list<string>
     */
    public function cabeceras(): array
    {
        return [
            'Cargo',
            ...array_values(self::COLUMNAS_DE_ACCESOS),
            ...array_values($this->columnasDeSoftware()),
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function filas(): array
    {
        $claves = [...array_keys(self::COLUMNAS_DE_ACCESOS), ...array_keys($this->columnasDeSoftware())];

        return array_map(
            fn (array $perfil): array => [
                $perfil['cargo'],
                ...array_map(
                    fn (string $clave): string => $this->celda($perfil['valores'][$clave] ?? []),
                    $claves,
                ),
            ],
            $this->perfiles(),
        );
    }

    /**
     * Categorías con datos: primero las del enum en su orden, luego las demás
     * del catálogo por nombre. La etiqueta es el nombre del catálogo.
     *
     * @return array<string, string> clave => etiqueta
     */
    private function columnasDeSoftware(): array
    {
        $conDatos = collect($this->perfiles())
            ->flatMap(fn (array $perfil): array => array_keys(array_filter($perfil['valores'])))
            ->unique()
            ->flip();

        $catalogo = CategoriaSoftware::query()
            ->orderBy('nombre')
            ->pluck('nombre', 'codigo');

        return collect(CategoriasSoftware::cases())
            ->map(fn (CategoriasSoftware $categoria): string => $categoria->value)
            ->merge($catalogo->keys())
            ->unique()
            ->reject(fn (string $codigo): bool => isset(self::CATEGORIA_AGRUPADA_EN[$codigo]))
            ->filter(fn (string $codigo): bool => $conDatos->has($codigo))
            ->mapWithKeys(fn (string $codigo): array => [
                $codigo => $catalogo->get($codigo) ?? CategoriasSoftware::tryFrom($codigo)?->getLabel() ?? $codigo,
            ])
            ->all();
    }

    /**
     * @return list<array{cargo: string, valores: array<string, list<string>>}>
     */
    private function perfiles(): array
    {
        return $this->perfiles ??= $this->cargos()
            ->map(fn (Cargo $cargo): array => [
                'cargo' => (string) $cargo->cargo,
                'valores' => $this->valoresDe($cargo->users, $this->softwareDelCargo($cargo)),
            ])
            ->values()
            ->all();
    }

    /**
     * Cargos con al menos una persona que no está de baja, ordenados por área y cargo.
     *
     * @return Collection<int, Cargo>
     */
    private function cargos(): Collection
    {
        $activa = fn (Relation $query): Relation => $query->where('estado', true);

        return Cargo::query()
            ->whereHas('users', fn (Builder $query): Builder => $this->noEstaDeBaja($query))
            ->with([
                'area',
                'users' => fn (Relation $query): Relation => $this->noEstaDeBaja($query),
                'softwareCargos.software.categoria',
                'users.accesosSistemas' => $activa,
                'users.accesosSistemas.sistema',
                'users.accesosSharepoint' => $activa,
                'users.accesosSharepoint.sitio',
                'users.accesosInternet' => $activa,
                'users.accesosVirtual' => $activa,
                'users.accesosVirtual.virtual',
                'users.accesosVirtual.recursoVpn',
                'users.unidadesCompartidas' => $activa,
                'users.softwareUsuarios' => $activa,
                'users.softwareUsuarios.software.categoria',
                'users.licenciasUsuarios' => $activa,
                'users.licenciasUsuarios.licencia',
                'users.azureAd' => $activa,
            ])
            ->get()
            ->sortBy([
                fn (Cargo $a, Cargo $b): int => strcasecmp((string) $a->area?->area, (string) $b->area?->area),
                fn (Cargo $a, Cargo $b): int => strcasecmp((string) $a->cargo, (string) $b->cargo),
            ]);
    }

    /**
     * @template TQuery of Builder|Relation
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    private function noEstaDeBaja(Builder|Relation $query): Builder|Relation
    {
        return $query->whereDoesntHave('ultimoMovimiento', fn (Builder $query): Builder => $query->where('tipo', 'Baja'));
    }

    /**
     * Une los valores de todas las personas del cargo, columna por columna.
     *
     * @param  Collection<int, User>  $usuarios
     * @param  Collection<int, Software>  $softwareDelCargo
     * @return array<string, list<string>>
     */
    private function valoresDe(Collection $usuarios, Collection $softwareDelCargo): array
    {
        $porColumna = $usuarios->map(fn (User $usuario): array => $this->valoresDelUsuario($usuario, $softwareDelCargo));

        $claves = $porColumna->flatMap(fn (array $valores): array => array_keys($valores))->unique();

        return $claves
            ->mapWithKeys(fn (string $clave): array => [
                $clave => $porColumna
                    ->flatMap(fn (array $valores): array => $valores[$clave] ?? [])
                    ->filter(fn (?string $valor): bool => filled($valor))
                    ->unique()
                    ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Software>  $softwareDelCargo
     * @return array<string, list<string|null>>
     */
    private function valoresDelUsuario(User $usuario, Collection $softwareDelCargo): array
    {
        $valores = [
            'sistemas' => $usuario->accesosSistemas
                ->map(fn (AccesoSistema $acceso): ?string => $this->conNivel($acceso->sistema?->nombre_sistema, $acceso->nivel_acceso))
                ->all(),
            'sharepoint' => $usuario->accesosSharepoint
                ->map(fn (AccesoSharepoint $acceso): ?string => $acceso->sitio?->nombre_sitio)
                ->all(),
            'internet' => $usuario->accesosInternet->isNotEmpty() && filled($usuario->area?->area)
                ? [SiglasArea::conNombre($usuario->area->area)]
                : [],
            'escritorio_remoto' => $usuario->accesosVirtual
                ->map(fn (AccesoVirtual $acceso): ?string => $acceso->virtual?->maquina_virtual)
                ->all(),
            'unidad_compartida' => $usuario->unidadesCompartidas
                ->map(fn (AccUdCompartida $acceso): ?string => $acceso->unidad_compartida)
                ->all(),
            'vpn' => $usuario->accesosVirtual
                ->map(fn (AccesoVirtual $acceso): ?string => $acceso->recursoVpn?->nombre_vpn)
                ->all(),
        ];

        foreach ($this->softwareDelUsuario($usuario, $softwareDelCargo) as $software) {
            $categoria = (string) $software->categoria?->codigo;
            $categoria = self::CATEGORIA_AGRUPADA_EN[$categoria] ?? $categoria;

            $valores[$categoria][] = $software->nombre_software;
        }

        // Como en el formulario: las licencias van en Microsoft y el MFA de
        // Azure AD en Autenticación multifactor.
        $valores[CategoriasSoftware::Microsoft->value] = [
            ...$valores[CategoriasSoftware::Microsoft->value] ?? [],
            ...$usuario->licenciasUsuarios->map(fn (LicenciaUsuario $asignacion): ?string => $asignacion->licencia?->nombre_licencia)->all(),
        ];

        if ($usuario->azureAd->contains(fn (AzureAd $cuenta): bool => (bool) $cuenta->mfa)) {
            $valores[CategoriasSoftware::AutenticacionMultifactor->value][] = 'Microsoft Authenticator';
        }

        return $valores;
    }

    /**
     * Software autorizado para el cargo.
     *
     * @return Collection<int, Software>
     */
    private function softwareDelCargo(Cargo $cargo): Collection
    {
        return $cargo->softwareCargos
            ->map(fn (SoftwareCargo $asignacion): ?Software => $asignacion->software)
            ->filter()
            ->values();
    }

    /**
     * Software activo asignado a la persona o a su cargo (sin excepciones).
     *
     * @param  Collection<int, Software>  $softwareDelCargo
     * @return Collection<int, Software>
     */
    private function softwareDelUsuario(User $usuario, Collection $softwareDelCargo): Collection
    {
        return collect()
            ->merge($usuario->softwareUsuarios->map(fn (SoftwareUsuario $asignacion): ?Software => $asignacion->software))
            ->merge($softwareDelCargo)
            ->filter(fn (?Software $software): bool => $software?->estado === true);
    }

    private function conNivel(?string $nombre, ?string $nivel): ?string
    {
        if (blank($nombre)) {
            return null;
        }

        return filled($nivel) ? "{$nombre} - {$nivel}" : $nombre;
    }

    /**
     * @param  list<string>  $valores
     */
    private function celda(array $valores): string
    {
        return $valores === [] ? self::VACIO : implode(self::SEPARADOR, $valores);
    }
}
