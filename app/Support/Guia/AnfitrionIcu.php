<?php

namespace App\Support\Guia;

use App\Http\Middleware\EnsureTwoFactorWhenRequired;
use App\Notifications\CriticalEventNotification;
use App\Services\Archivos\ArchivosService;
use CapitalSafi\Apariencia\AparienciaPlugin;
use CapitalSafi\Apariencia\Support\StudioBrand;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\User;
use SistemaIcu\Documentacion\Contracts\Anfitrion;

/**
 * Conecta la guía de usuario (paquete documentacion-sistema-icu) con el
 * Sistema ICU: le da la misma apariencia (plugin en solo lectura) y 2FA que el panel admin,
 * el disco confidencial para sus PDF y los avisos por la campana.
 *
 * Es el único código de la guía que vive en el sistema, junto con
 * `config/documentacion-icu.php` y el seeder de demostración.
 */
class AnfitrionIcu implements Anfitrion
{
    public function __construct(
        private ArchivosService $archivos,
    ) {}

    /**
     * La apariencia (colores, logos, favicon, fuente) la pone el plugin en modo
     * solo lectura: la edición vive únicamente en el panel admin.
     */
    public function personalizarPanel(Panel $panel): Panel
    {
        return $panel
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(fn (): string => asset('images/Capital-Safi-SA-logo-.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(fn (): string => asset('favicon.ico'))
            ->plugin(
                AparienciaPlugin::make()
                    ->settingsPage(false)
                    ->personalAppearance(false),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_BEFORE,
                fn (): View => view('filament.fonts'),
            )
            ->authMiddleware([
                EnsureTwoFactorWhenRequired::class,
            ]);
    }

    public function disco(): string
    {
        return $this->archivos->disco();
    }

    public function urlDelSistema(): string
    {
        return Filament::getPanel('admin')->getUrl();
    }

    public function logoParaPdf(): ?string
    {
        return StudioBrand::logoPath() ?? public_path('images/Capital-Safi-SA-logo-.png');
    }

    public function avisar(User $usuario, string $titulo, string $cuerpo, ?string $url = null, bool $exito = true): void
    {
        $usuario->notify(new CriticalEventNotification(
            title: $titulo,
            body: $cuerpo,
            url: $url,
            icon: $exito ? 'heroicon-o-document-arrow-down' : 'heroicon-o-x-circle',
            color: $exito ? 'success' : 'danger',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        ));
    }
}
