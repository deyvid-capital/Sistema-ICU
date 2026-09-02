<?php

namespace App\Filament\Clusters\Configuracion\Pages;

use App\Filament\Clusters\Configuracion\ConfiguracionCluster;
use App\Models\MailSetting;
use App\Services\Configuracion\MailConfigService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Credenciales SMTP editables desde el panel (contraseña cifrada en BD).
 */
class Correo extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.clusters.configuracion.pages.correo';

    protected static ?string $cluster = ConfiguracionCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Correo';

    protected static ?string $title = 'Correo SMTP';

    protected static ?string $slug = 'correo';

    protected static ?int $navigationSort = 4;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $formulario = app(MailConfigService::class)->paraFormulario();

        $this->form->fill([
            'activo' => $formulario['activo'],
            'mailer' => $formulario['mailer'],
            'host' => $formulario['host'],
            'port' => $formulario['port'],
            'scheme' => $formulario['scheme'],
            'encryption' => blank($formulario['encryption'] ?? null) ? 'null' : $formulario['encryption'],
            'require_tls' => $formulario['require_tls'],
            'username' => $formulario['username'],
            'password' => null,
            'from_address' => $formulario['from_address'],
            'from_name' => $formulario['from_name'],
            'ehlo_domain' => $formulario['ehlo_domain'],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Uso de esta configuración')
                    ->description('Si está activa, el sistema usa estos valores en lugar del archivo .env. Desactívala para volver al entorno.')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->schema([
                        Toggle::make('activo')
                            ->label('Usar configuración guardada en la base de datos')
                            ->helperText('La contraseña se guarda cifrada con APP_KEY. Nunca se muestra de nuevo en el formulario.')
                            ->live(),
                    ]),

                Section::make('Servidor SMTP')
                    ->description('Office 365: host smtp.office365.com, puerto 587, scheme smtp, cifrado tls.')
                    ->icon(Heroicon::OutlinedServer)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('mailer')
                                ->label('Mailer')
                                ->options([
                                    'smtp' => 'SMTP',
                                    'log' => 'Log (solo desarrollo)',
                                    'array' => 'Array (pruebas)',
                                ])
                                ->required()
                                ->native(false),
                            TextInput::make('host')
                                ->label('Host')
                                ->required(fn (Get $get): bool => (bool) $get('activo'))
                                ->maxLength(255),
                            TextInput::make('port')
                                ->label('Puerto')
                                ->numeric()
                                ->required(fn (Get $get): bool => (bool) $get('activo'))
                                ->minValue(1)
                                ->maxValue(65535),
                            Select::make('scheme')
                                ->label('Scheme')
                                ->options([
                                    'smtp' => 'smtp (STARTTLS, p. ej. 587)',
                                    'smtps' => 'smtps (SSL, p. ej. 465)',
                                ])
                                ->native(false),
                            Select::make('encryption')
                                ->label('Cifrado')
                                ->options([
                                    'tls' => 'TLS',
                                    'ssl' => 'SSL',
                                    'null' => 'Ninguno',
                                ])
                                ->native(false)
                                ->dehydrateStateUsing(fn (?string $state): ?string => $state === 'null' ? null : $state),
                            Toggle::make('require_tls')
                                ->label('Exigir TLS')
                                ->inline(false),
                        ]),
                    ]),

                Section::make('Credenciales')
                    ->description('Usa una contraseña de aplicación si la cuenta tiene MFA.')
                    ->icon(Heroicon::OutlinedKey)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('username')
                                ->label('Usuario')
                                ->email()
                                ->required(fn (Get $get): bool => (bool) $get('activo') && $get('mailer') === 'smtp')
                                ->maxLength(255),
                            TextInput::make('password')
                                ->label('Contraseña')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password')
                                ->helperText(fn (): string => app(MailConfigService::class)->tienePasswordGuardada()
                                    ? 'Hay una contraseña guardada. Déjalo vacío para no cambiarla.'
                                    : 'Todavía no hay contraseña guardada en la base de datos.')
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->required(fn (Get $get): bool => (bool) $get('activo')
                                    && $get('mailer') === 'smtp'
                                    && ! app(MailConfigService::class)->tienePasswordGuardada()),
                        ]),
                    ]),

                Section::make('Remitente')
                    ->icon(Heroicon::OutlinedAtSymbol)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('from_address')
                                ->label('Correo remitente')
                                ->email()
                                ->required(fn (Get $get): bool => (bool) $get('activo'))
                                ->maxLength(255),
                            TextInput::make('from_name')
                                ->label('Nombre remitente')
                                ->required(fn (Get $get): bool => (bool) $get('activo'))
                                ->maxLength(255),
                            TextInput::make('ehlo_domain')
                                ->label('Dominio EHLO')
                                ->helperText('Opcional. Por defecto se usa el host de APP_URL.')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('probar')
                ->label('Enviar correo de prueba')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->form([
                    TextInput::make('email')
                        ->label('Destinatario')
                        ->email()
                        ->required()
                        ->default(fn (): ?string => auth()->user()?->email),
                ])
                ->action(function (array $data): void {
                    $this->enviarPrueba((string) $data['email']);
                }),
            Action::make('guardar')
                ->label('Guardar cambios')
                ->icon(Heroicon::OutlinedCheck)
                ->action('guardar'),
        ];
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();

        $configuracion = MailSetting::query()->first() ?? new MailSetting;

        $payload = [
            'activo' => (bool) ($datos['activo'] ?? false),
            'mailer' => $datos['mailer'] ?? null,
            'host' => $datos['host'] ?? null,
            'port' => isset($datos['port']) ? (int) $datos['port'] : null,
            'scheme' => $datos['scheme'] ?? null,
            'encryption' => $datos['encryption'] ?? null,
            'require_tls' => (bool) ($datos['require_tls'] ?? false),
            'username' => $datos['username'] ?? null,
            'from_address' => $datos['from_address'] ?? null,
            'from_name' => $datos['from_name'] ?? null,
            'ehlo_domain' => $datos['ehlo_domain'] ?? null,
        ];

        if (filled($datos['password'] ?? null)) {
            $payload['password'] = $datos['password'];
        }

        $configuracion->fill($payload)->save();

        app(MailConfigService::class)->olvidar();
        app(MailConfigService::class)->aplicar();

        activity('configuracion')
            ->causedBy(auth()->user())
            ->performedOn($configuracion)
            ->withProperties([
                'activo' => $configuracion->activo,
                'host' => $configuracion->host,
                'username' => $configuracion->username,
                'from_address' => $configuracion->from_address,
                'password_actualizada' => filled($datos['password'] ?? null),
            ])
            ->log('Configuración de correo actualizada');

        Notification::make()
            ->success()
            ->title('Configuración de correo actualizada')
            ->send();

        $this->redirect(static::getUrl());
    }

    protected function enviarPrueba(string $email): void
    {
        app(MailConfigService::class)->aplicar();

        $enviadoAt = now()->timezone(config('app.display_timezone'))->format('d/m/Y H:i:s');
        $cuerpo = "Hola,\n\nEste es un correo de prueba del Sistema ICU.\n\nSi lo recibes, la configuración SMTP está funcionando correctamente.\n\nEnviado el: {$enviadoAt}";

        try {
            Mail::raw($cuerpo, function ($message) use ($email): void {
                $message->to($email)->subject('Prueba de correo - Sistema ICU');
            });

            Notification::make()
                ->success()
                ->title('Correo de prueba enviado')
                ->body("Revisa la bandeja de {$email} (y spam).")
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('No se pudo enviar el correo')
                ->body($exception->getMessage())
                ->persistent()
                ->send();
        }
    }
}
