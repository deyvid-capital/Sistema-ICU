<?php

namespace App\Notifications\Firma;

use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirma;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a un firmante de que tiene documentos por firmar. Siempre por campana y
 * correo. El correo no adjunta los PDF: solo lleva el enlace al sistema.
 */
class FirmaSolicitadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SolicitudFirma $solicitud)
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
            ->body("{$this->solicitud->creador?->name} te envió: {$this->solicitud->asunto}".$this->textoFechaLimite())
            ->icon('heroicon-o-pencil-square')
            ->iconColor('info')
            ->actions([
                Action::make('revisar')
                    ->label('Revisar y firmar')
                    ->button()
                    ->url($this->url())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $correo = (new MailMessage)
            ->subject("[Sistema ICU] {$this->titulo()}: {$this->solicitud->asunto}")
            ->greeting($this->titulo())
            ->line("**{$this->solicitud->creador?->name}** te envió **{$this->solicitud->asunto}** ({$this->solicitud->codigo}).");

        if (filled($this->solicitud->mensaje)) {
            $correo->line("> {$this->solicitud->mensaje}");
        }

        if ($this->solicitud->fecha_limite !== null) {
            $correo->line('**Fecha límite:** '.$this->solicitud->fecha_limite->format('d/m/Y'));
        }

        return $correo
            ->line('Para firmar necesitarás tu **contraseña del sistema ICU**.')
            ->action('Revisar y firmar', $this->url())
            ->line('Este es un aviso automático del Sistema ICU.');
    }

    private function titulo(): string
    {
        $cantidad = $this->solicitud->documentos()->count();

        return $cantidad === 1
            ? 'Tienes un documento por firmar'
            : "Tienes {$cantidad} documentos por firmar";
    }

    private function textoFechaLimite(): string
    {
        return $this->solicitud->fecha_limite !== null
            ? ' (fecha límite: '.$this->solicitud->fecha_limite->format('d/m/Y').')'
            : '';
    }

    private function url(): string
    {
        return SolicitudFirmaResource::getUrl('view', ['record' => $this->solicitud], panel: 'admin');
    }
}
