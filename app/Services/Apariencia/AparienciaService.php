<?php

namespace App\Services\Apariencia;

use App\Models\AppearanceSetting;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Resuelve la apariencia del panel combinando los temas de config/apariencia.php
 * con los valores guardados en la tabla `appearance_settings`.
 */
class AparienciaService
{
    public const CACHE_KEY = 'apariencia.resuelta';

    public const TEMA_PERSONALIZADO = 'personalizado';

    /**
     * Texto oscuro de referencia para decidir el contraste de las barras.
     */
    protected const TEXTO_OSCURO = '#0F1419';

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $resuelta = null;

    /**
     * @return array<string, mixed>
     */
    public function resolver(): array
    {
        return $this->resuelta ??= Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->construir());
    }

    public function olvidar(): void
    {
        $this->resuelta = null;

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Paletas para el método `colors()` del panel.
     *
     * @return array<string, array<int, string>|string>
     */
    public function coloresDelPanel(): array
    {
        $apariencia = $this->resolver();

        $colores = [
            'primary' => $this->paleta($apariencia['primario']),
            'gray' => $this->paleta($apariencia['gris']),
        ];

        foreach (config('apariencia.semanticos', []) as $nombre => $valor) {
            $colores[$nombre] = $this->paleta($valor);
        }

        return $colores;
    }

    /**
     * Tokens CSS del sidebar y de la barra superior, por modo de color.
     *
     * @return array{claro: array<string, array<string, string>>, oscuro: array<string, array<string, string>>, placaDeLogo: array{claro: bool, oscuro: bool}}
     */
    public function variablesCss(): array
    {
        $apariencia = $this->resolver();

        return [
            'claro' => [
                'sidebar' => $this->tokensDeBarra($apariencia['sidebar']['claro']),
                'topbar' => $this->tokensDeBarra($apariencia['topbar']['claro']),
            ],
            'oscuro' => [
                'sidebar' => $this->tokensDeBarra($apariencia['sidebar']['oscuro']),
                'topbar' => $this->tokensDeBarra($apariencia['topbar']['oscuro']),
            ],
            'placaDeLogo' => $this->placaDeLogo(),
        ];
    }

    public function logoClaro(): ?string
    {
        $archivo = $this->resolver()['logo_claro'];

        return filled($archivo)
            ? $this->url($archivo)
            : asset(config('apariencia.logo.fallback_claro'));
    }

    public function logoOscuro(): ?string
    {
        $archivo = $this->resolver()['logo_oscuro'];

        return filled($archivo) ? $this->url($archivo) : null;
    }

    public function altoLogo(): string
    {
        return $this->resolver()['alto_logo'];
    }

    public function favicon(): ?string
    {
        $archivo = $this->resolver()['favicon'];

        return filled($archivo)
            ? $this->url($archivo)
            : asset(config('apariencia.favicon.fallback'));
    }

    /**
     * ¿Hay que pintar una placa clara detrás del logo? Los logos corporativos
     * suelen ser oscuros sobre transparente y desaparecerían sobre un sidebar
     * oscuro. En modo `auto` solo se aplica cuando se usa el logo incluido en la
     * aplicación (del que sí sabemos que es oscuro).
     *
     * Se decide por barra porque el logo se dibuja en la superior cuando el
     * sidebar es colapsable, y en la cabecera del sidebar cuando no lo es.
     *
     * @return array{claro: array{sidebar: bool, topbar: bool}, oscuro: array{sidebar: bool, topbar: bool}}
     */
    public function placaDeLogo(): array
    {
        $apariencia = $this->resolver();
        $modo = $apariencia['fondo_logo'];

        $vacia = ['sidebar' => false, 'topbar' => false];

        if ($modo === 'ninguno') {
            return ['claro' => $vacia, 'oscuro' => $vacia];
        }

        $forzada = $modo === 'claro';
        $usaLogoIncluido = blank($apariencia['logo_claro']);
        $tieneLogoOscuro = filled($apariencia['logo_oscuro']);

        $aplica = fn (bool $permitida, string $fondo): bool => $permitida && $this->barraEsOscura($fondo);

        return [
            'claro' => [
                'sidebar' => $aplica($forzada || $usaLogoIncluido, $apariencia['sidebar']['claro']),
                'topbar' => $aplica($forzada || $usaLogoIncluido, $apariencia['topbar']['claro']),
            ],
            'oscuro' => [
                'sidebar' => $aplica($forzada || ($usaLogoIncluido && ! $tieneLogoOscuro), $apariencia['sidebar']['oscuro']),
                'topbar' => $aplica($forzada || ($usaLogoIncluido && ! $tieneLogoOscuro), $apariencia['topbar']['oscuro']),
            ],
        ];
    }

    public function barraEsOscura(string $fondo): bool
    {
        return $this->tokensDeBarra($fondo)['fg'] === '#FFFFFF';
    }

    /**
     * Fondo, color de texto y color del ítem activo para una barra. El texto y
     * el acento se eligen por contraste, de modo que cualquier fondo queda
     * legible. `activo` sirve para el CSS del panel (referencia a la paleta ya
     * registrada) y `activo_hex` para la vista previa del formulario.
     *
     * @return array<string, string>
     */
    public function tokensDeBarra(string $fondo, ?string $primario = null): array
    {
        $fondo = $this->normalizarColor($fondo, '#FFFFFF');

        $textoClaroEsLegible = Color::calculateContrastRatio($fondo, '#FFFFFF')
            >= Color::calculateContrastRatio($fondo, self::TEXTO_OSCURO);

        $texto = $textoClaroEsLegible ? '#FFFFFF' : 'var(--gray-950)';
        $paleta = $this->paletaPrimaria($primario);

        $tono = Color::findShade(
            palette: $paleta,
            surface: $fondo,
            minRatio: Color::WCAG_AA_TEXT,
            shouldStartFromDarkest: $textoClaroEsLegible,
        );

        return [
            'bg' => $fondo,
            'fg' => $texto,
            'fg_hex' => $textoClaroEsLegible ? '#FFFFFF' : self::TEXTO_OSCURO,
            'activo' => $tono !== null ? "var(--primary-{$tono})" : $texto,
            'activo_hex' => $tono !== null
                ? Color::convertToHex($paleta[$tono])
                : ($textoClaroEsLegible ? '#FFFFFF' : self::TEXTO_OSCURO),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function paletaPrimaria(?string $primario = null): array
    {
        $paleta = $this->paleta($primario ?: $this->resolver()['primario']);

        return is_array($paleta) ? $paleta : Color::hex($this->normalizarColor($paleta, '#003973'));
    }

    /**
     * Los colores llegan de un formulario, así que se validan antes de acabar
     * en una hoja de estilos.
     */
    protected function normalizarColor(?string $color, string $porDefecto): string
    {
        return is_string($color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1
            ? $color
            : $porDefecto;
    }

    /**
     * Acepta el nombre de una paleta de Filament (Slate, Zinc...) o un hexadecimal.
     *
     * @return array<int, string>|string
     */
    protected function paleta(string $valor): array|string
    {
        $constante = Color::class.'::'.$valor;

        if (defined($constante)) {
            /** @var array<int, string> $paleta */
            $paleta = constant($constante);

            return $paleta;
        }

        return $this->normalizarColor($valor, '#003973');
    }

    protected function url(string $archivo): string
    {
        return Storage::disk(config('apariencia.logo.disco'))->url($archivo);
    }

    /**
     * @return array<string, mixed>
     */
    protected function construir(): array
    {
        $fila = $this->leerFila();
        $temas = config('apariencia.temas', []);
        $porDefecto = $temas[config('apariencia.tema_por_defecto')] ?? reset($temas);

        $tema = $fila?->tema ?: config('apariencia.tema_por_defecto');
        $base = $temas[$tema] ?? $porDefecto;
        $esPersonalizado = $tema === self::TEMA_PERSONALIZADO;

        $valor = fn (?string $guardado, string $base): string => $esPersonalizado && filled($guardado)
            ? $guardado
            : $base;

        return [
            'tema' => $tema,
            'primario' => $valor($fila?->color_primario, $base['primario']),
            'gris' => $valor($fila?->color_gris, $base['gris']),
            'sidebar' => [
                'claro' => $valor($fila?->color_sidebar_claro, $base['sidebar']['claro']),
                'oscuro' => $valor($fila?->color_sidebar_oscuro, $base['sidebar']['oscuro']),
            ],
            'topbar' => [
                'claro' => $valor($fila?->color_topbar_claro, $base['topbar']['claro']),
                'oscuro' => $valor($fila?->color_topbar_oscuro, $base['topbar']['oscuro']),
            ],
            'logo_claro' => $fila?->logo_claro,
            'logo_oscuro' => $fila?->logo_oscuro,
            'favicon' => $fila?->favicon,
            'alto_logo' => $fila?->alto_logo ?: config('apariencia.logo.alto_por_defecto'),
            'fondo_logo' => $fila?->fondo_logo ?: 'auto',
        ];
    }

    /**
     * El panel también se inicializa en consola (migraciones, colas), donde la
     * tabla puede no existir todavía: en ese caso se usan los valores del config.
     */
    protected function leerFila(): ?AppearanceSetting
    {
        try {
            if (! Schema::hasTable('appearance_settings')) {
                return null;
            }

            return AppearanceSetting::query()->first();
        } catch (Throwable) {
            return null;
        }
    }
}
