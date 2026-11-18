<?php

namespace App\Console\Commands;

use App\Models\AppearanceSetting;
use App\Services\Archivos\ArchivosService;
use CapitalSafi\Apariencia\Settings\StudioSettings;
use CapitalSafi\Apariencia\Support\Images\ImageProfile;
use CapitalSafi\Apariencia\Support\Images\InvalidImageException;
use CapitalSafi\Apariencia\Support\Images\StudioImageProcessor;
use CapitalSafi\Apariencia\Support\StudioAssets;
use CapitalSafi\Apariencia\Support\StudioBrand;
use CapitalSafi\Apariencia\Support\StudioTheme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Lleva la marca guardada en `appearance_settings` (logos, favicon, alto, placa
 * y tema) a los ajustes del plugin `capitalsafi/filament-apariencia`.
 *
 * Cada imagen pasa por `StudioImageProcessor`, que la recodifica: nunca se copia
 * el archivo original. No borra nada de la tabla ni de los archivos antiguos
 * (eso es la tarea T8 del PLAN-002).
 */
class ImportarMarcaAparienciaCommand extends Command
{
    protected $signature = 'apariencia:importar-marca
                            {--dry-run : Solo muestra qué se importaría, sin escribir nada}
                            {--force : Importa aunque el plugin ya tenga logos o favicon}';

    protected $description = 'Importa el logo, el favicon, el alto, la placa y el tema de la marca antigua al plugin de apariencia';

    /**
     * Colecciones de logo de `AppearanceSetting` → ajuste del plugin.
     *
     * @var array<string, array{ajuste: string, etiqueta: string}>
     */
    private const LOGOS = [
        'logo_claro' => ['ajuste' => 'logo_light', 'etiqueta' => 'Logo claro'],
        'logo_oscuro' => ['ajuste' => 'logo_dark', 'etiqueta' => 'Logo oscuro'],
    ];

    /**
     * `fondo_logo` antiguo → `logo_plate` del plugin.
     *
     * @var array<string, string>
     */
    private const PLACAS = [
        'auto' => 'auto',
        'claro' => 'always',
        'ninguno' => 'never',
    ];

    /**
     * Temas antiguos que existen igual en el plugin. `personalizado` no se importa.
     *
     * @var list<string>
     */
    private const TEMAS = ['safi', 'confianza', 'turquesa', 'neutro'];

    /**
     * Formatos que el plugin no acepta (D6 y D7 del PLAN-002).
     *
     * @var list<string>
     */
    private const EXTENSIONES_RECHAZADAS = ['svg', 'ico'];

    /**
     * @var list<string>
     */
    private const MIMES_RECHAZADOS = ['image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];

    public function handle(StudioImageProcessor $procesador, ArchivosService $archivos): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! Schema::hasTable('appearance_settings')) {
            $this->warn('No existe la tabla appearance_settings: no hay nada que importar.');

            return self::SUCCESS;
        }

        $origen = AppearanceSetting::actual();

        if (! $origen->exists) {
            $this->info('No hay marca antigua guardada: no hay nada que importar.');

            return self::SUCCESS;
        }

        $ajustes = app(StudioSettings::class);

        if ($this->pluginYaTieneMarca($ajustes) && ! $this->option('force')) {
            $this->info('El plugin ya tiene logo o favicon: no se importa nada. Usa --force para repetir la importación.');

            return self::SUCCESS;
        }

        $this->info($dryRun ? 'Modo dry-run: no se escribe nada.' : 'Importando la marca al plugin de apariencia…');

        $discoPublico = $archivos->discoPublico();
        $antiguos = $this->rutasActuales($ajustes);
        $filas = [];

        foreach (self::LOGOS as $coleccion => $datos) {
            $resultado = $this->importarImagen($procesador, $origen, $coleccion, ImageProfile::Logo, $datos['etiqueta'], $discoPublico, $dryRun);

            if (is_string($resultado)) {
                if (! $dryRun) {
                    $ajustes->{$datos['ajuste']} = $resultado;
                }

                $filas[] = [$datos['etiqueta'], $dryRun ? 'se importaría' : $resultado];
            }
        }

        $favicon = $this->importarImagen($procesador, $origen, 'favicon', ImageProfile::Favicon, 'Favicon', $discoPublico, $dryRun);

        if ($favicon !== null) {
            if (is_array($favicon) && ! $dryRun) {
                $ajustes->favicon = $favicon['favicon'];
                $ajustes->favicon_touch = $favicon['favicon_touch'];
            }

            $filas[] = ['Favicon', $dryRun ? 'se importaría' : $ajustes->favicon.' + '.$ajustes->favicon_touch];
        }

        $this->importarAlto($origen, $ajustes, $dryRun, $filas);
        $this->importarPlaca($origen, $ajustes, $dryRun, $filas);
        $this->importarTema($origen, $ajustes, $dryRun, $filas);

        if ($filas === []) {
            $this->info('No había nada que importar.');

            return self::SUCCESS;
        }

        if (! $dryRun) {
            $ajustes->save();

            // Con --force se reemplaza una marca anterior: se borran sus archivos si nadie más los usa.
            StudioAssets::deleteUnused($antiguos);
            StudioBrand::flush();
        }

        $this->newLine();
        $this->table(['Elemento', $dryRun ? 'Resultado' : 'Importado'], $filas);

        return self::SUCCESS;
    }

    private function pluginYaTieneMarca(StudioSettings $ajustes): bool
    {
        return filled($ajustes->logo_light) || filled($ajustes->logo_dark) || filled($ajustes->favicon);
    }

    /**
     * @return array<string, string|null>
     */
    private function rutasActuales(StudioSettings $ajustes): array
    {
        return [
            'logo_light' => $ajustes->logo_light,
            'logo_dark' => $ajustes->logo_dark,
            'favicon' => $ajustes->favicon,
            'favicon_touch' => $ajustes->favicon_touch,
        ];
    }

    /**
     * Lee el archivo (Media Library o columna heredada), lo valida y, si no es
     * simulación, lo recodifica con el procesador del plugin.
     *
     * @return string|array{favicon: string, favicon_touch: string}|null null si se omite
     */
    private function importarImagen(
        StudioImageProcessor $procesador,
        AppearanceSetting $origen,
        string $coleccion,
        ImageProfile $perfil,
        string $etiqueta,
        string $discoPublico,
        bool $dryRun,
    ): string|array|null {
        $archivo = $this->leerOrigen($origen, $coleccion, $discoPublico, $etiqueta);

        if ($archivo === null) {
            return null;
        }

        if ($this->esFormatoRechazado($archivo['nombre'], $archivo['mime'])) {
            $this->warn("{$etiqueta}: «{$archivo['nombre']}» no se importa (el plugin no acepta SVG ni ICO). Sube un PNG desde Apariencia; mientras tanto se usa el respaldo.");

            return null;
        }

        $temporal = tempnam(sys_get_temp_dir(), 'marca-');

        if ($temporal === false || file_put_contents($temporal, $archivo['contenido']) === false) {
            $this->warn("{$etiqueta}: no se pudo preparar el archivo temporal.");

            return null;
        }

        try {
            if ($dryRun) {
                $procesador->validate($temporal, $perfil);
                $this->line("→ {$etiqueta}: {$archivo['nombre']}");

                return $perfil === ImageProfile::Favicon
                    ? ['favicon' => 'dry-run', 'favicon_touch' => 'dry-run']
                    : 'dry-run';
            }

            $resultado = $procesador->process($temporal, $perfil);
            $this->line("→ {$etiqueta}: {$archivo['nombre']}");

            return $resultado;
        } catch (InvalidImageException $excepcion) {
            $this->warn("{$etiqueta}: «{$archivo['nombre']}» no se importa. {$excepcion->getMessage()}");

            return null;
        } finally {
            @unlink($temporal);
        }
    }

    /**
     * @return array{nombre: string, mime: string|null, contenido: string}|null
     */
    private function leerOrigen(AppearanceSetting $origen, string $coleccion, string $discoPublico, string $etiqueta): ?array
    {
        $media = $origen->getFirstMedia($coleccion);

        if ($media !== null) {
            $contenido = rescue(
                fn () => Storage::disk($media->disk)->get($media->getPathRelativeToRoot()),
                null,
                false,
            );

            if (! is_string($contenido) || $contenido === '') {
                $this->warn("{$etiqueta}: no se pudo leer «{$media->file_name}» del disco {$media->disk}.");

                return null;
            }

            return ['nombre' => $media->file_name, 'mime' => $media->mime_type, 'contenido' => $contenido];
        }

        $ruta = $origen->getAttribute($coleccion);

        if (! filled($ruta)) {
            return null;
        }

        $contenido = rescue(fn () => Storage::disk($discoPublico)->get($ruta), null, false);

        if (! is_string($contenido) || $contenido === '') {
            $this->warn("{$etiqueta}: no se encontró el archivo «{$ruta}» en el disco {$discoPublico}.");

            return null;
        }

        return ['nombre' => basename((string) $ruta), 'mime' => null, 'contenido' => $contenido];
    }

    private function esFormatoRechazado(string $nombre, ?string $mime): bool
    {
        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        return in_array($extension, self::EXTENSIONES_RECHAZADAS, true)
            || ($mime !== null && in_array(strtolower($mime), self::MIMES_RECHAZADOS, true));
    }

    /**
     * @param  list<array{0: string, 1: string}>  $filas
     */
    private function importarAlto(AppearanceSetting $origen, StudioSettings $ajustes, bool $dryRun, array &$filas): void
    {
        $alto = $origen->alto_logo;

        if (! filled($alto)) {
            return;
        }

        if (preg_match('/^\d+(\.\d+)?(rem|px|em)$/', (string) $alto) !== 1) {
            $this->warn("Alto del logo: «{$alto}» no es un valor válido (por ejemplo 2.5rem); no se importa.");

            return;
        }

        if (! $dryRun) {
            $ajustes->logo_height = (string) $alto;
        }

        $filas[] = ['Alto del logo', (string) $alto];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $filas
     */
    private function importarPlaca(AppearanceSetting $origen, StudioSettings $ajustes, bool $dryRun, array &$filas): void
    {
        $placa = self::PLACAS[(string) $origen->fondo_logo] ?? null;

        if ($placa === null) {
            return;
        }

        if (! $dryRun) {
            $ajustes->logo_plate = $placa;
        }

        $filas[] = ['Placa del logo', $placa];
    }

    /**
     * Solo se aplica si el plugin sigue con el tema por defecto (o con --force),
     * para no pisar una elección hecha después en el editor de apariencia.
     *
     * @param  list<array{0: string, 1: string}>  $filas
     */
    private function importarTema(AppearanceSetting $origen, StudioSettings $ajustes, bool $dryRun, array &$filas): void
    {
        $tema = (string) $origen->tema;

        if (! in_array($tema, self::TEMAS, true) || ! array_key_exists($tema, config('apariencia.themes', []))) {
            return;
        }

        $temaPorDefecto = StudioTheme::default('theme');

        if (! $this->option('force') && filled($ajustes->theme) && $ajustes->theme !== $temaPorDefecto) {
            return;
        }

        if (! $dryRun) {
            foreach (StudioTheme::sanitizeValues(StudioTheme::themeValues($tema)) as $clave => $valor) {
                if (property_exists($ajustes, $clave)) {
                    $ajustes->{$clave} = $valor;
                }
            }
        }

        $filas[] = ['Tema', $tema];
    }
}
