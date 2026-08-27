<?php

namespace App\Filament\Pages\Auth;

use App\Support\Auth\RecuperacionPassword;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function getTitle(): string|Htmlable
    {
        return 'Olvidé mi contraseña';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Te enviaremos un código';
    }

    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();
        $email = app(RecuperacionPassword::class)->normalizarEmail((string) ($data['email'] ?? ''));

        app(RecuperacionPassword::class)->solicitar($email, request()->ip());

        Notification::make()
            ->title('Revisa tu correo')
            ->body(RecuperacionPassword::MENSAJE_GENERICO)
            ->success()
            ->send();

        $this->redirect(app(RecuperacionPassword::class)->urlRestablecer($email));
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->helperText('Si el correo está en el sistema, recibirás un código alfanumérico válido por '.config('acceso.recuperacion.codigo_ttl_minutos', 10).' minutos.');
    }
}
