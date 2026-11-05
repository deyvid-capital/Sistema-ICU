<?php

namespace App\Notifications\Firma;

use App\Filament\Pages\MiFirmaDigital;
use App\Notifications\Firma\Concerns\RespetaPreferenciaFirma;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al titular de que el admin activó (o renovó) su firma. No tiene que hacer
 * nada: la firma se vincula con su contraseña del sistema al iniciar sesión.
 * Por campana y correo, según la preferencia "Firma digital" del usuario.
 */
class FirmaAsignadaNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespetaPreferenciaFirma;

    public function __construct(public bool $esRenovacion = false)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->canalesPermitidos($notifiable, ['database', 'mail']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->titulo())
            ->body($this->cuerpo())
            ->icon('heroicon-o-finger-print')
            ->iconColor('success')
            ->actions([
                Action::make('ver')
                    ->label('Ver mi firma digital')
                    ->button()
                    ->url(MiFirmaDigital::getUrl(panel: 'admin'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Sistema ICU] '.$this->titulo())
            ->greeting($this->titulo())
            ->line($this->cuerpo())
            ->line('**No tienes que activar nada.** Firmarás con la misma contraseña con la que entras al Sistema ICU.')
            ->line('La firma queda vinculada a tu contraseña la próxima vez que inicies sesión (o la primera vez que firmes, si ya tienes la sesión abierta).')
            ->action('Ver mi firma digital', MiFirmaDigital::getUrl(panel: 'admin'))
            ->line('Este es un aviso automático del Sistema ICU.');
    }

    private function titulo(): string
    {
        return $this->esRenovacion
            ? 'Tu firma digital fue renovada'
            : 'Tu firma digital está lista';
    }

    private function cuerpo(): string
    {
        return $this->esRenovacion
            ? 'Un administrador cargó y activó el nuevo archivo .p12 de tu firma digital. Reemplaza al anterior.'
            : 'Un administrador cargó y activó tu firma digital. Ya puedes firmar documentos en el sistema.';
    }
}
