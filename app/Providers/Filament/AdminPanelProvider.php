<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\Accesos\AccesosCluster;
use App\Filament\Clusters\Asignacion\AsignacionCluster;
use App\Filament\Clusters\Celulares\CelularesCluster;
use App\Filament\Clusters\Configuracion\Pages\Archivos;
use App\Filament\Clusters\Configuracion\Pages\Correo;
use App\Filament\Clusters\Configuracion\Pages\FirmaDigital;
use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Clusters\Inventario\InventarioCluster;
use App\Filament\Clusters\Recursos\RecursosCluster;
use App\Filament\Clusters\Software\SoftwareCluster;
use App\Filament\Clusters\Usuarios\UsuariosCluster;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use App\Filament\Pages\MiFirmaDigital;
use App\Filament\Resources\Activitylogs\ActivitylogResource;
use App\Filament\Resources\CertificadosFirma\CertificadoFirmaResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use App\Services\Firma\RecifrarCertificadoFirma;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CapitalSafi\Apariencia\AparienciaPlugin;
use CapitalSafi\Breezy\BreezyPlugin;
use Filament\Actions\Action;
use Filament\Enums\UserMenuPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Forms\Components\TextInput;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Platform;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Rmsramos\Activitylog\ActivitylogPlugin;
use SistemaIcu\Documentacion\Filament\Pages\Inicio as GuiaInicio;

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
            // Respaldos fijos: el plugin de apariencia los reemplaza por la marca subida.
            ->brandLogo(fn (): string => asset('images/Capital-Safi-SA-logo-.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(fn (): string => asset('favicon.ico'))
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->topbar(false)
            ->userMenuItems([
                Action::make('configuraciones')
                    ->label('Configuraciones')
                    ->url(function (): string {
                        foreach ([Archivos::class, Correo::class, FirmaDigital::class] as $pagina) {
                            if ($pagina::canAccess()) {
                                return $pagina::getUrl();
                            }
                        }

                        return CertificadoFirmaResource::canViewAny()
                            ? CertificadoFirmaResource::getUrl()
                            : Archivos::getUrl();
                    })
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->visible(fn (): bool => Archivos::canAccess() || Correo::canAccess()
                        || FirmaDigital::canAccess() || CertificadoFirmaResource::canViewAny()),
                Action::make('mi_firma_digital')
                    ->label('Mi firma digital')
                    ->url(fn (): string => MiFirmaDigital::getUrl())
                    ->icon(Heroicon::OutlinedFingerPrint)
                    ->visible(fn (): bool => MiFirmaDigital::tieneFirmaAsignada()),
                Action::make('roles')
                    ->label('Roles y permisos')
                    ->url(fn (): string => RoleResource::getUrl())
                    ->icon(Heroicon::OutlinedKey)
                    ->visible(fn (): bool => RoleResource::canViewAny()),
                Action::make('guia_usuario')
                    ->label('Guía de usuario')
                    ->url(fn (): string => GuiaInicio::getUrl(panel: 'guia'))
                    ->icon(Heroicon::OutlinedBookOpen),
                Action::make('registro_actividad')
                    ->label('Registro de actividad')
                    ->url(fn (): string => ActivitylogResource::getUrl())
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->visible(fn (): bool => ActivitylogResource::canViewAny()),
            ])

            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
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
            ->renderHook(
                PanelsRenderHook::STYLES_BEFORE,
                fn (): View => view('filament.fonts'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
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
                // Por ahora solo el super administrador elige la apariencia global.
                AparienciaPlugin::make()
                    ->canCustomize(fn (): bool => (bool) auth()->user()?->hasRole(config('filament-shield.super_admin.name'))),
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
                // Perfil, 2FA y sesiones: preset de Capital SAFI + lo propio del ICU.
                // La ruta /configurar-2fa y el middleware del 2FA obligatorio los registra el plugin.
                BreezyPlugin::capitalSafi()
                    ->personalInfo(
                        fields: fn (User $user): array => self::camposInformacionPersonal($user),
                        readOnly: ['carnet', 'email'],
                        saveUsing: function (User $user, array $data): void {
                            $data['name'] = trim(implode(' ', array_filter(
                                [$data['nombres'] ?? null, $data['apellidos'] ?? null],
                                fn (?string $valor): bool => filled($valor),
                            )));

                            $user->update($data);
                        },
                        heading: 'Información personal',
                        description: 'Correo y carnet no se editan aquí.',
                    )
                    ->password(
                        // Vuelve a cifrar la contraseña del certificado .p12 en la misma transacción,
                        // para que la firma digital siga activa.
                        afterChange: fn (User $user, string $currentPassword, string $newPassword, string $newHash) => app(RecifrarCertificadoFirma::class)($user, $currentPassword, $newPassword, $newHash),
                        heading: 'Contraseña',
                        description: 'Mínimo 8 caracteres, con mayúsculas, minúsculas y un número.',
                    )
                    ->twoFactor(
                        required: fn (User $user): bool => (bool) $user->requiere_2fa,
                        // El resto lo resetea un administrador desde Seguridad del usuario.
                        canDisable: fn (User $user): bool => $user->hasAnyRole(['super_admin', 'admin_ti', 'admin']),
                    )
                    ->browserSessions(
                        heading: 'Sesiones del navegador',
                        description: 'Cierre las sesiones que no reconozca.',
                    ),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Campos de «Información personal» en «Mi perfil». Carnet y correo son de solo lectura
     * (los cambia un administrador); el plugin nunca los guarda.
     *
     * @return array<int, TextInput>
     */
    protected static function camposInformacionPersonal(User $user): array
    {
        return [
            TextInput::make('nombres')
                ->label('Nombres')
                ->placeholder('Ej. Juan Carlos')
                ->required()
                ->maxLength(100)
                ->autocomplete('given-name'),
            TextInput::make('apellidos')
                ->label('Apellidos')
                ->placeholder('Ej. Pérez López')
                ->required()
                ->maxLength(100)
                ->autocomplete('family-name'),
            TextInput::make('carnet')
                ->label('Carnet')
                ->helperText('Si necesita corregirlo, solicite el cambio.'),
            TextInput::make('email')
                ->label('Correo electrónico')
                ->email()
                ->helperText('Si necesita uno distinto, solicítelo al administrador.')
                ->prefixIcon(Heroicon::OutlinedEnvelope),
            TextInput::make('telefono')
                ->label('Teléfono')
                ->placeholder('Ej. 7123-4567')
                ->tel()
                ->maxLength(30)
                ->unique(User::class, 'telefono', ignorable: $user)
                ->nullable()
                ->dehydrateStateUsing(fn (mixed $state): ?string => filled($state) ? $state : null)
                ->prefixIcon(Heroicon::OutlinedPhone)
                ->columnSpanFull(),
        ];
    }
}
