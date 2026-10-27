<?php

namespace App\Notifications\Firma;

use App\Filament\Pages\MiFirmaDigital;
use App\Filament\Resources\CertificadosFirma\CertificadoFirmaResource;
use App\Notifications\Firma\Concerns\RespetaPreferenciaFirma;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * El certificado de una firma vence pronto (a 30, 15 y 7 días). Una versión para el
 * titular (pedir la renovación) y otra para los admins (subir el nuevo .p12).
 */
class CertificadoPorVencerNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespetaPreferenciaFirma;

    public function __construct(
        public string $titularNombre,
        public string $venceEl,
        public int $dias,
        public bool $paraAdministrador = false,
    ) {
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
            ->icon('heroicon-o-calendar-days')
            ->iconColor($this->dias <= 7 ? 'danger' : 'warning')
            ->actions([
                Action::make('ver')
                    ->label($this->paraAdministrador ? 'Ver firmas digitales' : 'Ver mi firma')
                    ->button()
                    ->url($this->url())
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
            ->action($this->paraAdministrador ? 'Ver firmas digitales' : 'Ver mi firma', $this->url())
            ->line('Este es un aviso automático del Sistema ICU.');
    }

    private function titulo(): string
    {
        $cuando = match ($this->dias) {
            0 => 'hoy',
            1 => 'mañana',
            default => "en {$this->dias} días",
        };

        return $this->paraAdministrador
            ? "La firma digital de {$this->titularNombre} vence {$cuando}"
            : "Tu firma digital vence {$cuando}";
    }

    private function cuerpo(): string
    {
        return $this->paraAdministrador
            ? "El certificado vence el {$this->venceEl}. Cuando el usuario tenga el nuevo .p12 de Digicert, súbelo en Configuración → Firmas digitales → Renovar."
            : "Tu certificado vence el {$this->venceEl}. Solicita la renovación a Digicert y entrega el nuevo archivo .p12 al administrador; después de vencer ya no podrás firmar.";
    }

    private function url(): string
    {
        return $this->paraAdministrador
            ? CertificadoFirmaResource::getUrl(panel: 'admin')
            : MiFirmaDigital::getUrl(panel: 'admin');
    }
}
