<?php

namespace App\Notifications\Firma;

use App\Enums\EventoSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirma;
use App\Notifications\Firma\Concerns\RespetaPreferenciaFirma;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avance de un envío a firmar: alguien firmó, terminó, se rechazó, se canceló o
 * venció el plazo. Los canales salen del evento y se filtran por la preferencia
 * "Firma digital" del usuario. El correo no adjunta los PDF.
 */
class SolicitudFirmaNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespetaPreferenciaFirma;

    public function __construct(
        public SolicitudFirma $solicitud,
        public EventoSolicitudFirma $evento,
        public string $titulo,
        public string $cuerpo,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->canalesPermitidos($notifiable, $this->evento->canales());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->titulo)
            ->body($this->cuerpo)
            ->icon($this->evento->icono())
            ->iconColor($this->evento->color())
            ->actions([
                Action::make('ver')
                    ->label($this->textoBoton())
                    ->button()
                    ->url($this->url())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Sistema ICU] {$this->titulo}")
            ->greeting($this->titulo)
            ->line($this->cuerpo)
            ->line("**Envío:** {$this->solicitud->codigo} · {$this->solicitud->asunto}")
            ->action($this->textoBoton(), $this->url())
            ->line('Este es un aviso automático del Sistema ICU.');
    }

    private function textoBoton(): string
    {
        return $this->evento === EventoSolicitudFirma::Finalizada ? 'Ver y descargar' : 'Ver envío';
    }

    private function url(): string
    {
        return SolicitudFirmaResource::getUrl('view', ['record' => $this->solicitud], panel: 'admin');
    }
}
