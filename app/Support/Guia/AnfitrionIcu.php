<?php

namespace App\Support\Guia;

use App\Http\Middleware\EnsureTwoFactorWhenRequired;
use App\Notifications\CriticalEventNotification;
use App\Services\Archivos\ArchivosService;
use App\Services\Configuracion\TemaService;
use Filament\Enums\UserMenuPosition;
use Filament\Facades\Filament;
use Filament\FontProviders\LocalFontProvider;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\User;
use SistemaIcu\Documentacion\Contracts\Anfitrion;

/**
 * Conecta la guía de usuario (paquete documentacion-sistema-icu) con el
 * Sistema ICU: le da el mismo tema, logo, colores y 2FA que el panel admin,
 * el disco confidencial para sus PDF y los avisos por la campana.
 *
 * Es el único código de la guía que vive en el sistema, junto con
 * `config/documentacion-icu.php` y el seeder de demostración.
 */
class AnfitrionIcu implements Anfitrion
{
    public function __construct(
        private TemaService $tema,
        private ArchivosService $archivos,
    ) {}

    public function personalizarPanel(Panel $panel): Panel
    {
        return $panel
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(fn (): ?string => $this->tema->logoClaro())
            ->darkModeBrandLogo(fn (): ?string => $this->tema->logoOscuro())
            ->brandLogoHeight(fn (): string => $this->tema->altoLogo())
            ->favicon(fn (): ?string => $this->tema->favicon())
            ->colors(fn (): array => $this->tema->coloresDelPanel())
            ->font('Lato', provider: LocalFontProvider::class)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->renderHook(
                PanelsRenderHook::STYLES_BEFORE,
                fn (): View => view('filament.fonts'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.configuracion.estilos', $this->tema->variablesCss()),
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
        return public_path('images/Capital-Safi-SA-logo-.png');
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
