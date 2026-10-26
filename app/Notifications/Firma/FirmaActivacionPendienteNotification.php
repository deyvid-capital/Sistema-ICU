<?php

namespace App\Notifications\Firma;

use App\Filament\Pages\MiFirmaDigital;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al titular de que el admin cargó (o renovó) su .p12 y debe activarlo.
 * Siempre por campana y correo: no depende de las preferencias de notificación.
 */
class FirmaActivacionPendienteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $esRenovacion = false)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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
            ->iconColor('info')
            ->actions([
                Action::make('activar')
                    ->label('Activar mi firma')
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
            ->line('**Cómo activarla (2 minutos):**')
            ->line('1. Entra al sistema y ve a **Menú de usuario → Mi firma digital**.')
            ->line('2. Paso 1: escribe la contraseña de tu archivo **.p12** (la de Digicert/Viafirma, **no** la del sistema).')
            ->line('3. Paso 2: confirma tu **contraseña del sistema ICU**. Desde ese momento firmarás con ella.')
            ->action('Activar mi firma', MiFirmaDigital::getUrl(panel: 'admin'))
            ->line('Este es un aviso automático del Sistema ICU.');
    }

    private function titulo(): string
    {
        return $this->esRenovacion
            ? 'Activa la renovación de tu firma digital'
            : 'Activa tu firma digital';
    }

    private function cuerpo(): string
    {
        return $this->esRenovacion
            ? 'Se cargó el nuevo archivo .p12 de tu firma digital. Tu firma actual sigue funcionando hasta que actives la nueva.'
            : 'Un administrador cargó tu firma digital (.p12). Debes activarla para poder firmar documentos en el sistema.';
    }
}
