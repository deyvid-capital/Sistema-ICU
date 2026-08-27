<?php

namespace App\Filament\Pages\Auth;

use App\Support\Auth\EstadoRecuperacion;
use App\Support\Auth\RecuperacionPassword;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password as PasswordRule;
use SensitiveParameter;

class ResetPassword extends BaseResetPassword
{
    public ?string $code = '';

    public function mount(?string $email = null, #[SensitiveParameter] ?string $token = null): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->token = $token ?? request()->query('token');
        $this->email = $email ?? request()->query('email');

        $this->form->fill([
            'email' => $this->email,
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Restablecer contraseña';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Código y nueva contraseña';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEstadoPlaceholder(),
                $this->getEmailFormComponent(),
                $this->getCodeFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $recuperacion = app(RecuperacionPassword::class);
        $estado = $recuperacion->estado((string) $this->email);

        if ($estado->bloqueado) {
            $this->notificarBloqueo($estado->bloqueadoHasta?->timezone(config('app.display_timezone'))->format('H:i') ?? '');

            return null;
        }

        $data = $this->form->getState();

        $resultado = $recuperacion->restablecer(
            (string) $this->email,
            (string) ($this->code ?? $data['code'] ?? ''),
            (string) ($data['password'] ?? $this->password ?? ''),
        );

        if ($resultado->ok) {
            Notification::make()
                ->title('Contraseña actualizada')
                ->body($resultado->mensaje)
                ->success()
                ->send();

            return app(PasswordResetResponse::class);
        }

        Notification::make()
            ->title($resultado->bloqueado ? 'Recuperación bloqueada' : 'No se pudo restablecer')
            ->body($resultado->mensaje)
            ->danger()
            ->send();

        return null;
    }

    public function reenviar(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $recuperacion = app(RecuperacionPassword::class);
        $estado = $recuperacion->estado((string) $this->email);

        if ($estado->bloqueado) {
            $this->notificarBloqueo($estado->bloqueadoHasta?->timezone(config('app.display_timezone'))->format('H:i') ?? '');

            return;
        }

        if ($estado->segundosReenvio > 0) {
            Notification::make()
                ->title('Espera un momento')
                ->body("Podrás pedir otro código en {$estado->segundosReenvio} segundo(s).")
                ->warning()
                ->send();

            return;
        }

        $recuperacion->solicitar((string) $this->email, request()->ip());

        Notification::make()
            ->title('Revisa tu correo')
            ->body(RecuperacionPassword::MENSAJE_GENERICO)
            ->success()
            ->send();
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getResetPasswordFormAction()
                ->disabled(fn (): bool => app(RecuperacionPassword::class)->estado((string) $this->email)->bloqueado),
            Action::make('reenviar')
                ->label('Reenviar código')
                ->color('gray')
                ->action('reenviar')
                ->disabled(fn (): bool => app(RecuperacionPassword::class)->estado((string) $this->email)->segundosReenvio > 0),
        ];
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->disabled();
    }

    protected function getCodeFormComponent(): Component
    {
        $bloqueado = app(RecuperacionPassword::class)->estado((string) $this->email)->bloqueado;

        return TextInput::make('code')
            ->label('Código del correo')
            ->placeholder('ABCD-2345')
            ->required()
            ->autocomplete('one-time-code')
            ->extraInputAttributes([
                'class' => 'text-center tracking-widest uppercase',
                'inputmode' => 'text',
            ])
            ->helperText('8 caracteres alfanuméricos. El guion es opcional.')
            ->disabled($bloqueado)
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        $bloqueado = app(RecuperacionPassword::class)->estado((string) $this->email)->bloqueado;

        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/password-reset/reset-password.form.password.label'))
            ->password()
            ->autocomplete('new-password')
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(PasswordRule::default())
            ->same('passwordConfirmation')
            ->disabled($bloqueado)
            ->validationAttribute(__('filament-panels::auth/pages/password-reset/reset-password.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        $bloqueado = app(RecuperacionPassword::class)->estado((string) $this->email)->bloqueado;

        return TextInput::make('passwordConfirmation')
            ->label(__('filament-panels::auth/pages/password-reset/reset-password.form.password_confirmation.label'))
            ->password()
            ->autocomplete('new-password')
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->disabled($bloqueado)
            ->dehydrated(false);
    }

    protected function getEstadoPlaceholder(): Component
    {
        return Placeholder::make('estado_otp')
            ->hiddenLabel()
            ->content(function (): HtmlString {
                $estado = app(RecuperacionPassword::class)->estado((string) $this->email);

                if ($estado->bloqueado) {
                    $segundos = $estado->segundosBloqueo;

                    return new HtmlString(
                        '<div class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-400/10 dark:text-danger-400" wire:poll.1s>'
                        .'La recuperación está bloqueada. Faltan <strong>'.$segundos.'</strong> segundo(s). No se enviará otro correo hasta entonces.'
                        .'</div>'
                    );
                }

                if ($estado->tieneCodigoActivo) {
                    return new HtmlString(
                        '<div class="rounded-lg bg-primary-50 p-3 text-sm text-primary-700 dark:bg-primary-400/10 dark:text-primary-400" wire:poll.1s>'
                        .'El código del correo caduca en <strong>'.$estado->segundosExpiracionCodigo.'</strong> segundo(s). '
                        .'Te quedan <strong>'.$estado->intentosRestantes.'</strong> intento(s).'
                        .'</div>'
                    );
                }

                return new HtmlString(
                    '<div class="rounded-lg bg-gray-50 p-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">'
                    .'Si pediste un código, escríbelo aquí junto con tu nueva contraseña. Si ya caducó, reenvíalo.'
                    .'</div>'
                );
            });
    }

    protected function notificarBloqueo(string $hora): void
    {
        Notification::make()
            ->title('Recuperación bloqueada')
            ->body($hora !== ''
                ? "Demasiados códigos incorrectos. No puedes validar ni pedir un código nuevo hasta las {$hora}."
                : 'Demasiados códigos incorrectos. Espera a que termine el bloqueo para volver a intentar.')
            ->danger()
            ->send();
    }

    protected function estadoRecuperacion(): EstadoRecuperacion
    {
        return once(fn (): EstadoRecuperacion => app(RecuperacionPassword::class)->estado((string) $this->email));
    }
}
