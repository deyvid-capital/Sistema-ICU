<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\Accesos\AccesosCluster;
use App\Filament\Clusters\Celulares\CelularesCluster;
use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Clusters\Inventario\InventarioCluster;
use App\Filament\Clusters\Recursos\RecursosCluster;
use App\Filament\Clusters\Software\SoftwareCluster;
use App\Filament\Clusters\Usuarios\UsuariosCluster;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Actions\Action;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Platform;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentIcon::register([
            'panels::sidebar.collapse-button' => Heroicon::Bars3BottomLeft,
            'panels::sidebar.expand-button' => Heroicon::Bars3BottomRight,
        ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(asset('images/Capital-Safi-SA-logo-.png'))
            ->brandLogoHeight('2.5rem')
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->userMenuItems([
                Action::make('roles')
                    ->label('Roles y permisos')
                    ->url(fn (): string => RoleResource::getUrl())
                    ->icon(Heroicon::OutlinedKey)
                    ->visible(fn (): bool => RoleResource::canViewAny()),
            ])
            
            ->login()
            ->font('Roboto Mono')
            ->topNavigation(false)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldSuffix(fn (): ?string => match (Platform::detect()) {
                Platform::Windows, Platform::Linux => 'CTRL+K',
                Platform::Mac => '⌘K',
                default => null,
            })
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->databaseNotifications()
            
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Zinc,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
                Dashboard::class,
                UsuariosCluster::class,
                SoftwareCluster::class,
                AccesosCluster::class,
                RecursosCluster::class,
                EquiposCluster::class,
                CelularesCluster::class,
                InventarioCluster::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->registerNavigation(false),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
