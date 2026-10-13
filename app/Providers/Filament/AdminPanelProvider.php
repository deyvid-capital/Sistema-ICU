<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\Accesos\AccesosCluster;
use App\Filament\Clusters\Asignacion\AsignacionCluster;
use App\Filament\Clusters\Celulares\CelularesCluster;
use App\Filament\Clusters\Configuracion\Pages\Archivos;
use App\Filament\Clusters\Configuracion\Pages\Correo;
use App\Filament\Clusters\Configuracion\Pages\Marca;
use App\Filament\Clusters\Configuracion\Pages\TemaColores;
use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Clusters\Inventario\InventarioCluster;
use App\Filament\Clusters\Recursos\RecursosCluster;
use App\Filament\Clusters\Software\SoftwareCluster;
use App\Filament\Clusters\Usuarios\UsuariosCluster;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use App\Filament\Pages\Auth\SetupTwoFactor;
use App\Filament\Pages\Auth\TwoFactorChallenge;
use App\Filament\Resources\Activitylogs\ActivitylogResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Http\Middleware\EnsureTwoFactorWhenRequired;
use App\Livewire\Profile\PersonalInfo;
use App\Livewire\Profile\TwoFactorAuthentication;
use App\Livewire\Profile\UpdatePassword as ProfileUpdatePassword;
use App\Services\Configuracion\TemaService;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Enums\UserMenuPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Platform;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Rmsramos\Activitylog\ActivitylogPlugin;

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
            ->brandLogo(fn (): ?string => app(TemaService::class)->logoClaro())
            ->darkModeBrandLogo(fn (): ?string => app(TemaService::class)->logoOscuro())
            ->brandLogoHeight(fn (): string => app(TemaService::class)->altoLogo())
            ->favicon(fn (): ?string => app(TemaService::class)->favicon())
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->topbar(false)
            ->userMenuItems([
                Action::make('configuraciones')
                    ->label('Configuraciones')
                    ->url(function (): string {
                        foreach ([TemaColores::class, Marca::class, Archivos::class, Correo::class] as $pagina) {
                            if ($pagina::canAccess()) {
                                return $pagina::getUrl();
                            }
                        }

                        return TemaColores::getUrl();
                    })
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->visible(fn (): bool => TemaColores::canAccess() || Marca::canAccess() || Archivos::canAccess() || Correo::canAccess()),
                Action::make('roles')
                    ->label('Roles y permisos')
                    ->url(fn (): string => RoleResource::getUrl())
                    ->icon(Heroicon::OutlinedKey)
                    ->visible(fn (): bool => RoleResource::canViewAny()),
                Action::make('registro_actividad')
                    ->label('Registro de actividad')
                    ->url(fn (): string => ActivitylogResource::getUrl())
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->visible(fn (): bool => ActivitylogResource::canViewAny()),
            ])

            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->authenticatedRoutes(function (): void {
                Route::get('/configurar-2fa', SetupTwoFactor::class)
                    ->name('auth.setup-two-factor');
            })
            ->font('Lato', provider: LocalFontProvider::class)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldSuffix(fn (): ?string => match (Platform::detect()) {
                Platform::Windows, Platform::Linux => 'CTRL+K',
                Platform::Mac => '⌘K',
                default => null,
            })

            ->sidebarWidth('18rem')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Equipos'),
            ])
            ->databaseNotifications()
            ->colors(fn (): array => app(TemaService::class)->coloresDelPanel())
            ->renderHook(
                PanelsRenderHook::STYLES_BEFORE,
                fn (): View => view('filament.fonts'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.configuracion.estilos', app(TemaService::class)->variablesCss()),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
                Dashboard::class,
                UsuariosCluster::class,
                SoftwareCluster::class,
                AccesosCluster::class,
                RecursosCluster::class,
                InventarioCluster::class,
                EquiposCluster::class,
                AsignacionCluster::class,
                CelularesCluster::class,
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
                    ->registerNavigation(false)
                    ->gridColumns(['default' => 1, 'lg' => 2])
                    ->sectionColumnSpan(1)
                    ->resourceCheckboxListColumns(['default' => 1, 'sm' => 2]),
                ActivitylogPlugin::make()
                    ->resource(ActivitylogResource::class)
                    ->label('Registro de actividad')
                    ->pluralLabel('Registros de actividad')
                    ->navigationItem(false)
                    ->navigationIcon('heroicon-o-clipboard-document-list')
                    ->resourceActionLabel('Ver recurso')
                    ->isRestoreModelActionHidden(false)
                    ->dateFormat('d/m/Y')
                    ->datetimeFormat('d/m/Y H:i:s')
                    ->dateParser(fn (mixed $date): Carbon => Carbon::parse($date)->timezone(config('app.display_timezone')))
                    ->customizeDatetimeColumn(fn ($column) => $column->timezone(config('app.display_timezone')))
                    ->translateSubject(fn (string $label): string => trans()->has('activity.subjects.'.$label)
                        ? __('activity.subjects.'.$label)
                        : Str::headline($label))
                    ->translateLogKey(fn (string $label): string => trans()->has('activity.keys.'.$label)
                        ? __('activity.keys.'.$label)
                        : Str::headline(str_replace('_', ' ', $label)))
                    ->authorize(fn (): bool => (bool) auth()->user()?->can('ViewAny:Activity')),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        userMenuLabel: 'Mi perfil',
                        shouldRegisterNavigation: false,
                        navigationGroup: 'Configuración',
                        hasAvatars: false,
                        slug: 'mi-perfil'
                    )
                    ->passwordUpdateRules(
                        rules: [Password::default()],
                        requiresCurrentPassword: true,
                    )
                    ->enableTwoFactorAuthentication(
                        force: false,
                        action: TwoFactorChallenge::class,
                        scopeToPanel: true,
                    )
                    ->enableBrowserSessions(condition: true)
                    ->myProfileComponents([
                        'personal_info' => PersonalInfo::class,
                        'update_password' => ProfileUpdatePassword::class,
                        'two_factor_authentication' => TwoFactorAuthentication::class,
                    ]),
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureTwoFactorWhenRequired::class,
            ]);
    }
}
