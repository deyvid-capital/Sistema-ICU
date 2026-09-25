<?php

namespace Database\Seeders;

use App\Enums\CategoriasSoftware;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\Area;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\RecursoVpn;
use App\Models\Sistema;
use App\Models\SitioSharepoint;
use App\Models\Software;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Models\Virtual;
use Database\Seeders\Concerns\ReadsCsvData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InventarioAccesosSeeder extends Seeder
{
    use ReadsCsvData;

    public function run(): void
    {
        $rows = $this->rowsFromCsv('Data-Complet.csv');

        $this->asegurarCuentaPaginaWeb($rows);

        Model::withoutEvents(function () use ($rows): void {
            $usuarios = User::query()->pluck('id', 'email');

            $this->seedSoftware($rows, $usuarios);
            $this->seedLicencias($rows, $usuarios);
            $this->seedSistemas($rows, $usuarios);
            $this->seedSharepoint($rows, $usuarios);
            $this->seedUnidades($rows, $usuarios);
            $this->seedEscritorios($rows, $usuarios);
            $this->seedRecursosVpn($rows);
        });
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function asegurarCuentaPaginaWeb(array $rows): void
    {
        $fila = collect($rows)->first(
            fn (array $row): bool => strtolower($row['CORREO'] ?? '') === 'web@capitalsafi.com',
        );

        if ($fila === null) {
            return;
        }

        $areaId = Area::query()->where('area', 'Marketing')->value('id_area');

        User::query()->firstOrCreate(
            ['email' => 'web@capitalsafi.com'],
            [
                'id' => (new User)->newUniqueId(),
                'name' => 'Pagina Web',
                'nombres' => 'Pagina Web',
                'apellidos' => null,
                'carnet' => null,
                'id_area' => $areaId,
                'id_cargo' => null,
                'acceso_habilitado' => false,
            ],
        );
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  Collection<string, string>  $usuarios
     */
    private function seedSoftware(array $rows, Collection $usuarios): void
    {
        $filas = array_values(array_filter($rows, $this->esSoftware(...)));
        $nombres = [];

        foreach ($filas as $fila) {
            $nombre = $this->nombreSoftware($fila['RECURSO']);
            $nombres[$nombre][] = $fila['SUBCATEGORIA'];
        }

        $ids = [];

        foreach ($nombres as $nombre => $subcategorias) {
            $software = Software::query()->updateOrCreate(
                ['nombre_software' => $nombre],
                [
                    'categoria' => $this->categoriaSoftware($nombre, $subcategorias),
                    'estado' => true,
                ],
            );
            $ids[$nombre] = $software->id_software;
        }

        $asignados = [];

        foreach ($filas as $fila) {
            $usuarioId = $this->usuarioId($fila, $usuarios);

            if ($usuarioId === null) {
                continue;
            }

            $nombre = $this->nombreSoftware($fila['RECURSO']);
            $clave = $usuarioId.'|'.$ids[$nombre];

            if (isset($asignados[$clave])) {
                continue;
            }

            $asignados[$clave] = true;

            SoftwareUsuario::query()->updateOrCreate(
                [
                    'usuario_id' => $usuarioId,
                    'id_software' => $ids[$nombre],
                ],
                ['estado' => true],
            );
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  Collection<string, string>  $usuarios
     */
    private function seedLicencias(array $rows, Collection $usuarios): void
    {
        $filas = array_values(array_filter(
            $rows,
            fn (array $row): bool => $row['CATEGORIA'] === 'Licencia',
        ));

        $porLicencia = [];

        foreach ($filas as $fila) {
            $usuarioId = $this->usuarioId($fila, $usuarios);

            if ($usuarioId === null) {
                continue;
            }

            $porLicencia[$fila['RECURSO']][$usuarioId] = $this->fecha($fila['FECHA_ALTA']);
        }

        foreach ($porLicencia as $nombre => $asignaciones) {
            $licencia = Licencia::query()->updateOrCreate(
                ['nombre_licencia' => $nombre],
                [
                    'categoria' => 'MICROSOFT',
                    'estado' => true,
                    'total_licencias' => count($asignaciones),
                ],
            );

            foreach ($asignaciones as $usuarioId => $fecha) {
                LicenciaUsuario::query()->updateOrCreate(
                    [
                        'usuario_id' => $usuarioId,
                        'licencia_id' => $licencia->id,
                    ],
                    [
                        'fecha' => $fecha,
                        'estado' => true,
                    ],
                );
            }
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  Collection<string, string>  $usuarios
     */
    private function seedSistemas(array $rows, Collection $usuarios): void
    {
        $filas = array_values(array_filter(
            $rows,
            fn (array $row): bool => $row['CATEGORIA'] === 'Sistema',
        ));

        /** @var array<string, array<string, array{niveles: list<string>, fecha: ?Carbon}>> $grupos */
        $grupos = [];

        foreach ($filas as $fila) {
            $usuarioId = $this->usuarioId($fila, $usuarios);

            if ($usuarioId === null) {
                continue;
            }

            $nombre = $fila['RECURSO'];
            $grupos[$nombre][$usuarioId] ??= [
                'niveles' => [],
                'fecha' => $this->fecha($fila['FECHA_ALTA']),
                'email' => strtolower($fila['CORREO']),
            ];

            $nivel = $this->nullableCsvValue($fila['DETALLE']);

            if ($nivel !== null && ! in_array($nivel, $grupos[$nombre][$usuarioId]['niveles'], true)) {
                $grupos[$nombre][$usuarioId]['niveles'][] = $nivel;
            }
        }

        foreach ($grupos as $nombre => $accesos) {
            $sistema = Sistema::query()->updateOrCreate(
                ['nombre_sistema' => $nombre],
                ['nombre_sistema' => $nombre],
            );

            foreach ($accesos as $usuarioId => $acceso) {
                [$nivel, $observaciones] = $this->nivelDeAcceso($acceso['niveles']);

                AccesoSistema::query()->updateOrCreate(
                    [
                        'usuario_id' => $usuarioId,
                        'id_sistema' => $sistema->id_sistema,
                    ],
                    [
                        'usuario_sistema' => Str::limit(Str::before($acceso['email'], '@'), 50, ''),
                        'nivel_acceso' => $nivel,
                        'estado' => true,
                        'fecha_inicio' => $acceso['fecha'] ?? now()->toDateString(),
                        'observaciones' => $observaciones,
                    ],
                );
            }
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  Collection<string, string>  $usuarios
     */
    private function seedSharepoint(array $rows, Collection $usuarios): void
    {
        $filas = array_values(array_filter(
            $rows,
            fn (array $row): bool => $row['CATEGORIA'] === 'SharePoint',
        ));

        $ids = [];

        foreach ($filas as $fila) {
            $nombre = $fila['RECURSO'];

            if (isset($ids[$nombre])) {
                continue;
            }

            $sitio = SitioSharepoint::query()->firstOrCreate(
                ['nombre_sitio' => $nombre],
                [
                    'url' => 'https://pendiente-sharepoint.local/sites/'.Str::slug($nombre),
                    'estado' => true,
                ],
            );
            $ids[$nombre] = $sitio->id;
        }

        foreach ($filas as $fila) {
            $usuarioId = $this->usuarioId($fila, $usuarios);

            if ($usuarioId === null) {
                continue;
            }

            AccesoSharepoint::query()->updateOrCreate(
                [
                    'usuario_id' => $usuarioId,
                    'sitio_id' => $ids[$fila['RECURSO']],
                ],
                ['estado' => true],
            );
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  Collection<string, string>  $usuarios
     */
    private function seedUnidades(array $rows, Collection $usuarios): void
    {
        foreach ($rows as $fila) {
            if ($fila['CATEGORIA'] !== 'Unidad compartida') {
                continue;
            }

            $usuarioId = $this->usuarioId($fila, $usuarios);

            if ($usuarioId === null) {
                continue;
            }

            AccUdCompartida::query()->updateOrCreate(
                [
                    'usuario_id' => $usuarioId,
                    'unidad_compartida' => $fila['RECURSO'],
                ],
                ['estado' => true],
            );
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  Collection<string, string>  $usuarios
     */
    private function seedEscritorios(array $rows, Collection $usuarios): void
    {
        foreach ($rows as $fila) {
            if ($fila['CATEGORIA'] !== 'Escritorio remoto') {
                continue;
            }

            $usuarioId = $this->usuarioId($fila, $usuarios);

            if ($usuarioId === null) {
                continue;
            }

            $virtual = Virtual::query()->firstOrCreate(
                ['maquina_virtual' => $fila['RECURSO']],
                ['estado' => true],
            );

            AccesoVirtual::query()->updateOrCreate(
                [
                    'usuario_id' => $usuarioId,
                    'virtual_id' => $virtual->id,
                    'vpn_id' => null,
                ],
                ['estado' => true],
            );
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function seedRecursosVpn(array $rows): void
    {
        $nombres = [];

        foreach ($rows as $fila) {
            if ($fila['CATEGORIA'] !== 'VPN' || $fila['SUBCATEGORIA'] !== 'Acceso VPN') {
                continue;
            }

            $nombres[$fila['RECURSO']] = true;
        }

        foreach (array_keys($nombres) as $nombre) {
            RecursoVpn::query()->firstOrCreate(
                ['nombre_vpn' => $nombre],
                ['estado' => true],
            );
        }

        $this->command?->warn('Los accesos VPN por usuario no se asignaron: la tabla exige una máquina virtual y el CSV no indica cuál.');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function esSoftware(array $row): bool
    {
        return $row['CATEGORIA'] === 'Software'
            || ($row['CATEGORIA'] === 'VPN' && $row['SUBCATEGORIA'] === 'Software VPN');
    }

    private function nombreSoftware(string $recurso): string
    {
        return match ($recurso) {
            'FortiClient -Marketing' => 'FortiClient',
            'Microsoft Autenticator' => 'Microsoft Authenticator',
            default => $recurso,
        };
    }

    /**
     * @param  list<string>  $subcategorias
     */
    private function categoriaSoftware(string $nombre, array $subcategorias): string
    {
        if ($nombre === 'FortiClient' || in_array('Software VPN', $subcategorias, true)) {
            return CategoriasSoftware::SoftwareVPN->value;
        }

        $subcategoria = $subcategorias[0];

        return match ($subcategoria) {
            'Antivirus y VPN' => CategoriasSoftware::Antivirus->value,
            'Compresión' => CategoriasSoftware::Compresion->value,
            'Doble autenticación' => CategoriasSoftware::AutenticacionMultifactor->value,
            'Impresora y escáner' => str_contains(mb_strtolower($nombre), 'scan')
                ? CategoriasSoftware::Scanner->value
                : CategoriasSoftware::Impresora->value,
            'Navegadores' => CategoriasSoftware::Navegadores->value,
            'PDF' => CategoriasSoftware::PDF->value,
            'Software de escáner' => CategoriasSoftware::SoftwareEscaner->value,
            'Soporte remoto' => CategoriasSoftware::SoporteRemoto->value,
            default => CategoriasSoftware::Otro->value,
        };
    }

    /**
     * @param  list<string>  $niveles
     * @return array{0: string, 1: ?string}
     */
    private function nivelDeAcceso(array $niveles): array
    {
        if ($niveles === []) {
            return ['Usuario', null];
        }

        $texto = implode(', ', $niveles);

        if (mb_strlen($texto) <= 100) {
            return [$texto, null];
        }

        return [$niveles[0], 'Otros niveles: '.implode(', ', array_slice($niveles, 1))];
    }

    /**
     * @param  array<string, string>  $fila
     * @param  Collection<string, string>  $usuarios
     */
    private function usuarioId(array $fila, Collection $usuarios): ?string
    {
        $email = strtolower($fila['CORREO']);
        $usuarioId = $usuarios->get($email);

        if ($usuarioId === null) {
            $this->command?->warn("Sin usuario para {$email}. Se omitió {$fila['RECURSO']}.");
        }

        return $usuarioId;
    }

    private function fecha(?string $valor): ?Carbon
    {
        $valor = $this->nullableCsvValue($valor);

        if ($valor === null) {
            return null;
        }

        $fecha = Carbon::createFromFormat('!j/n/Y', $valor);

        return $fecha === false ? null : $fecha;
    }
}
