<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Pantalla dedicada de enrolamiento 2FA (estilo Microsoft Authenticator)
 * para usuarios con `requiere_2fa` que aún no confirmaron el segundo factor.
 */
class SetupTwoFactor extends SimplePage implements HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;
    use WithRateLimiting;

    protected string $view = 'filament.pages.auth.setup-two-factor';

    protected bool $hasTopbar = false;

    protected Width|string|null $maxWidth = Width::ThreeExtraLarge;

    /**
     * install | setup
     */
    public string $step = 'install';

    /**
     * @var array{code?: string|null}
     */
    public array $data = [];

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return Filament::getPanel($panel ?? 'admin')->route('auth.setup-two-factor', $parameters, $isAbsolute);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Configurar verificación en dos pasos';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function mount(): RedirectResponse|Redirector|null
    {
        if (! Filament::auth()->check()) {
            return redirect()->to(Filament::getLoginUrl());
        }

        /** @var User $user */
        $user = Filament::auth()->user();

        if ($user->hasConfirmedTwoFactor()) {
            return redirect()->to(Filament::getHomeUrl());
        }

        if (! $user->requiere_2fa) {
            return redirect()->to(Filament::getHomeUrl());
        }

        if ($user->hasEnabledTwoFactor()) {
            $this->step = 'setup';
        }

        return null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código de verificación')
                    ->placeholder('000000')
                    ->autocomplete('one-time-code')
                    ->inputMode('numeric')
                    ->extraInputAttributes(['class' => 'text-center tracking-[0.3em] text-lg font-semibold'])
                    ->required()
                    ->length(6),
            ])
            ->statePath('data');
    }

    public function empezar(): void
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        if (! $user->hasEnabledTwoFactor()) {
            $user->enableTwoFactorAuthentication();
            $user->refresh();
        }

        $this->step = 'setup';
        $this->resetErrorBag();
        $this->data = ['code' => null];
    }

    public function confirmar(): RedirectResponse|Redirector|null
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->addError('data.code', 'Demasiados intentos. Espera '.$exception->secondsUntilAvailable.' segundos.');

            return null;
        }

        $this->form->getState();

        $code = (string) ($this->data['code'] ?? '');

        /** @var User $user */
        $user = Filament::auth()->user();

        if (! filament('filament-breezy')->verify(code: $code)) {
            app(AuditorSeguridad::class)->dosFactoresCodigoIncorrecto($user);

            $this->addError('data.code', 'El código no es válido. Revisa la app e inténtalo de nuevo.');

            return null;
        }

        $user->confirmTwoFactorAuthentication();
        $user->setTwoFactorSession();
        $user->refresh();

        app(AuditorSeguridad::class)->dosFactoresConfirmado($user);

        Notification::make()
            ->success()
            ->title('Verificación en dos pasos activada')
            ->send();

        return redirect()->to(Filament::getHomeUrl());
    }

    public function getTwoFactorSecretProperty(): string
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        return $user->breezySession?->two_factor_secret ?? '';
    }

    public function getTwoFactorQrCode(): string
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        if (! $user->breezySession?->two_factor_secret) {
            return '';
        }

        return filament('filament-breezy')->getTwoFactorQrCodeSvg($user->getTwoFactorQrCodeUrl());
    }

    /**
     * @return array{app_store: string, play_store: string}
     */
    public function getStoreUrls(): array
    {
        return [
            'app_store' => 'https://apps.apple.com/app/microsoft-authenticator/id983156458',
            'play_store' => 'https://play.google.com/store/apps/details?id=com.azure.authenticator',
        ];
    }
}
