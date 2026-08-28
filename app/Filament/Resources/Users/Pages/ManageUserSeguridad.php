<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\CanalNotificacion;
use App\Enums\TipoAcceso;
use App\Enums\TipoEventoNotificacion;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\Seguridad\ProteccionSuperAdmin;
use App\Support\Sessions\UserSessions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ManageUserSeguridad extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $navigationLabel = 'Seguridad';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function canAccess(array $parameters = []): bool
    {
        return parent::canAccess($parameters)
            && (bool) auth()->user()?->can('manageSecurity', User::class);
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Seguridad — '.$this->getRecordTitle();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Seguridad')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    $this->tabAcceso(),
                    $this->tabRoles(),
                    $this->tabDosFactores(),
                    $this->tabSesiones(),
                    $this->tabNotificaciones(),
                ]),
        ]);
    }

    protected function tabAcceso(): Tab
    {
        return Tab::make('Acceso')
            ->icon(Heroicon::OutlinedKey)
            ->schema([
                Toggle::make('acceso_habilitado')
                    ->label('Acceso habilitado')
                    ->onColor('success')
                    ->offColor('danger')
                    ->disabled(fn (): bool => ProteccionSuperAdmin::esPropio($this->record))
                    ->helperText(fn (): string => ProteccionSuperAdmin::esPropio($this->record)
                        ? 'No puedes cambiar tu propio acceso. Pídele a otro super administrador con acceso vigente que lo haga.'
                        : 'Si se desactiva, el usuario no puede entrar al panel (se cierran sus sesiones al guardar).'),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Select::make('tipo_acceso')
                        ->label('Vigencia del acceso')
                        ->options([
                            TipoAcceso::Indefinido->value => TipoAcceso::Indefinido->getLabel(),
                            TipoAcceso::Definido->value => TipoAcceso::Definido->getLabel(),
                        ])
                        ->default(TipoAcceso::Indefinido->value)
                        ->selectablePlaceholder(false)
                        ->native(false)
                        ->live()
                        ->disabled(fn (): bool => ProteccionSuperAdmin::esPropio($this->record))
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if ($state === TipoAcceso::Indefinido->value) {
                                $set('acceso_expira_at', null);
                            }
                        })
                        ->dehydrated(false),
                    DateTimePicker::make('acceso_expira_at')
                        ->label('Acceso hasta')
                        ->placeholder('Selecciona fecha y hora límite')
                        ->seconds(false)
                        ->minDate(now())
                        ->disabled(fn (): bool => ProteccionSuperAdmin::esPropio($this->record))
                        ->required(fn (Get $get): bool => $get('tipo_acceso') === TipoAcceso::Definido->value)
                        ->hidden(fn (Get $get): bool => $get('tipo_acceso') !== TipoAcceso::Definido->value)
                        ->dehydrateStateUsing(fn ($state, Get $get) => $get('tipo_acceso') === TipoAcceso::Definido->value ? $state : null),
                ]),
                Placeholder::make('estado_bloqueo')
                    ->label('Estado de bloqueo')
                    ->content(fn (): string => $this->record->estaBloqueado()
                        ? 'Bloqueado desde '.$this->record->bloqueado_at?->format('d/m/Y H:i').' ('.$this->record->intentos_fallidos.' intentos fallidos).'
                        : 'Sin bloqueo ('.$this->record->intentos_fallidos.' intentos fallidos registrados).'),
                Actions::make([
                    Action::make('desbloquear')
                        ->label('Desbloquear usuario')
                        ->icon(Heroicon::OutlinedLockOpen)
                        ->color('warning')
                        ->visible(fn (): bool => $this->record->estaBloqueado())
                        ->requiresConfirmation()
                        ->modalHeading('Desbloquear usuario')
                        ->modalDescription('Se reinicia el contador de intentos y el usuario podrá volver a iniciar sesión.')
                        ->action(function (): void {
                            $this->record->desbloquear();
                            $this->record->refresh();

                            Notification::make()->success()->title('Usuario desbloqueado')->send();
                        }),
                ]),
            ]);
    }

    protected function tabRoles(): Tab
    {
        return Tab::make('Roles')
            ->icon(Heroicon::OutlinedUserGroup)
            ->visible(fn (): bool => (bool) auth()->user()?->hasRole('super_admin'))
            ->schema([
                Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->disableOptionWhen(function (string $value): bool {
                        $rolSuperAdminId = ProteccionSuperAdmin::idRolSuperAdmin();

                        return $rolSuperAdminId !== null
                            && (int) $value === $rolSuperAdminId
                            && ProteccionSuperAdmin::esPropio($this->record)
                            && ! ProteccionSuperAdmin::existeOtroSuperAdminActivo($this->record);
                    })
                    ->helperText(fn (): string => ProteccionSuperAdmin::esPropio($this->record)
                        && ! ProteccionSuperAdmin::existeOtroSuperAdminActivo($this->record)
                        ? 'No puedes quitarte super_admin: no hay otro super administrador con acceso vigente.'
                        : 'Sin al menos un rol, el usuario no puede entrar al panel.'),
            ]);
    }

    protected function tabDosFactores(): Tab
    {
        return Tab::make('2FA')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->schema([
                Placeholder::make('estado_2fa')
                    ->label('Estado de la verificación en dos pasos')
                    ->content(fn (): string => match (true) {
                        $this->record->hasConfirmedTwoFactor() => 'Activa y confirmada.',
                        $this->record->hasEnabledTwoFactor() => 'Iniciada pero sin confirmar.',
                        default => 'No configurada.',
                    }),
                Toggle::make('requiere_2fa')
                    ->label('Exigir 2FA a este usuario')
                    ->onColor('success')
                    ->helperText('Si se activa, el usuario deberá configurar el 2FA antes de poder usar el sistema.'),
                Actions::make([
                    Action::make('resetear2fa')
                        ->label('Resetear / quitar 2FA')
                        ->icon(Heroicon::OutlinedExclamationTriangle)
                        ->color('danger')
                        ->visible(fn (): bool => $this->record->hasEnabledTwoFactor() || $this->record->hasConfirmedTwoFactor())
                        ->requiresConfirmation()
                        ->modalHeading('Resetear autenticación de dos factores')
                        ->modalDescription('Elimina el 2FA del usuario. Úsalo si perdió el acceso a su app de autenticación o dejó la organización. Deberá volver a configurarlo.')
                        ->action(function (): void {
                            $this->record->breezySessions()->withoutGlobalScopes()->delete();
                            $this->record->refresh();

                            Notification::make()->success()->title('2FA restablecido')->send();
                        }),
                ]),
            ]);
    }

    protected function tabSesiones(): Tab
    {
        return Tab::make('Sesiones')
            ->icon(Heroicon::OutlinedComputerDesktop)
            ->schema([
                Placeholder::make('sesiones_activas')
                    ->label('Sesiones activas')
                    ->content(function (): HtmlString|string {
                        $sesiones = UserSessions::active($this->record);

                        if ($sesiones->isEmpty()) {
                            return 'El usuario no tiene sesiones activas.';
                        }

                        $items = $sesiones->map(function (array $s): string {
                            $dispositivo = trim(($s['browser'] ?? 'Navegador desconocido').' · '.($s['platform'] ?? 'Plataforma desconocida'), ' ·');
                            $ip = $s['ip_address'] ?? 'IP desconocida';
                            $activo = Carbon::createFromTimestamp($s['last_active'])->diffForHumans();

                            return '<li>'.e($dispositivo).' — '.e($ip).' — '.e($activo).'</li>';
                        })->implode('');

                        return new HtmlString('<ul class="list-disc ps-4 space-y-1">'.$items.'</ul>');
                    }),
                Actions::make([
                    Action::make('cerrarSesiones')
                        ->label('Cerrar todas las sesiones')
                        ->icon(Heroicon::OutlinedArrowRightStartOnRectangle)
                        ->color('danger')
                        ->visible(fn (): bool => ProteccionSuperAdmin::puedeCerrarSesiones($this->record)
                            && UserSessions::active($this->record)->isNotEmpty())
                        ->requiresConfirmation()
                        ->modalHeading('Cerrar todas las sesiones')
                        ->modalDescription('El usuario será desconectado de todos sus dispositivos.')
                        ->action(function (): void {
                            if (! ProteccionSuperAdmin::puedeCerrarSesiones($this->record)) {
                                Notification::make()
                                    ->title('Acción no permitida')
                                    ->body('No puedes cerrar tus propias sesiones desde aquí.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $cerradas = UserSessions::terminate($this->record);

                            Notification::make()
                                ->success()
                                ->title('Sesiones cerradas')
                                ->body("Se cerraron {$cerradas} sesión(es).")
                                ->send();
                        }),
                ]),
            ]);
    }

    protected function tabNotificaciones(): Tab
    {
        $fieldsets = [];

        foreach (TipoEventoNotificacion::cases() as $tipo) {
            $toggles = [];

            foreach (CanalNotificacion::cases() as $canal) {
                $toggles[] = Toggle::make($this->prefKey($tipo->value, $canal->value))
                    ->label($canal->getLabel())
                    ->default(true)
                    ->dehydrated(false)
                    ->inline(false);
            }

            $fieldsets[] = Fieldset::make($tipo->getLabel())
                ->schema($toggles)
                ->columns(count($toggles));
        }

        return Tab::make('Notificaciones')
            ->icon(Heroicon::OutlinedBell)
            ->schema($fieldsets);
    }

    protected function prefKey(string $tipo, string $canal): string
    {
        return "pref__{$tipo}__{$canal}";
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tipo_acceso'] = $this->record->acceso_expira_at !== null
            ? TipoAcceso::Definido->value
            : TipoAcceso::Indefinido->value;

        foreach (TipoEventoNotificacion::cases() as $tipo) {
            foreach (CanalNotificacion::cases() as $canal) {
                $data[$this->prefKey($tipo->value, $canal->value)] = $this->record->debeRecibir($tipo->value, $canal->value);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tipo = $this->data['tipo_acceso'] ?? TipoAcceso::Indefinido->value;

        if ($tipo === TipoAcceso::Indefinido->value) {
            $data['acceso_expira_at'] = null;
        } elseif (array_key_exists('acceso_expira_at', $this->data)) {
            $data['acceso_expira_at'] = $this->data['acceso_expira_at'];
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $errores = ProteccionSuperAdmin::erroresAlGuardar($this->record, $this->data, $this->data);

        if ($errores !== []) {
            Notification::make()
                ->title('No se pudo guardar la seguridad')
                ->body(implode(' ', $errores))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        // Cierra sesiones si el acceso quedó deshabilitado.
        if (! $this->record->acceso_habilitado) {
            UserSessions::terminate($this->record);
        }

        // Persiste la matriz de preferencias de notificación.
        foreach (TipoEventoNotificacion::cases() as $tipo) {
            foreach (CanalNotificacion::cases() as $canal) {
                $this->record->notificationPreferences()->updateOrCreate(
                    ['tipo_evento' => $tipo->value, 'canal' => $canal->value],
                    ['habilitado' => (bool) ($this->data[$this->prefKey($tipo->value, $canal->value)] ?? true)],
                );
            }
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Configuración de seguridad guardada');
    }
}
