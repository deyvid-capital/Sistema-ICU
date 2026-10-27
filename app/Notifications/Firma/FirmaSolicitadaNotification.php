<?php

namespace App\Notifications\Firma;

use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirma;
use App\Models\User;
use App\Notifications\Firma\Concerns\RespetaPreferenciaFirma;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a un firmante de que tiene documentos por firmar, o recordatorio de que
 * siguen pendientes. Por campana y correo, según la preferencia "Firma digital".
 * El correo no adjunta los PDF: solo lleva el enlace al sistema.
 */
class FirmaSolicitadaNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespetaPreferenciaFirma;

    public function __construct(
        public SolicitudFirma $solicitud,
        public bool $esRecordatorio = false,
        public bool $vencido = false,
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
            ->title($this->titulo($notifiable))
            ->body("{$this->solicitud->creador?->name} te envió: {$this->solicitud->asunto}".$this->textoFechaLimite())
            ->icon('heroicon-o-pencil-square')
            ->iconColor($this->vencido ? 'danger' : ($this->esRecordatorio ? 'warning' : 'info'))
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
            ->subject("[Sistema ICU] {$this->titulo($notifiable)}: {$this->solicitud->asunto}")
            ->greeting($this->titulo($notifiable))
            ->line("**{$this->solicitud->creador?->name}** te envió **{$this->solicitud->asunto}** ({$this->solicitud->codigo}).");

        if (filled($this->solicitud->mensaje)) {
            $correo->line("> {$this->solicitud->mensaje}");
        }

        if ($this->solicitud->fecha_limite !== null) {
            $correo->line('**Fecha límite:** '.$this->solicitud->fecha_limite->format('d/m/Y').($this->vencido ? ' (vencida)' : ''));
        }

        if ($this->esRecordatorio) {
            $correo->line('Este recordatorio se repite cada '.$this->solicitud->recordatorio_cada_horas.' horas, en horario hábil, mientras tengas firmas pendientes.');
        }

        return $correo
            ->line('Para firmar necesitarás tu **contraseña del sistema ICU**.')
            ->action('Revisar y firmar', $this->url())
            ->line('Este es un aviso automático del Sistema ICU.');
    }

    /**
     * Cuenta lo que le falta firmar a esta persona (en un recordatorio pueden ser menos que al inicio).
     */
    private function titulo(object $notifiable): string
    {
        $pendientes = $notifiable instanceof User ? $this->solicitud->firmasPendientesDe($notifiable)->count() : 0;
        $cantidad = $pendientes > 0 ? $pendientes : $this->solicitud->documentos()->count();

        $titulo = $cantidad === 1
            ? 'Tienes un documento por firmar'
            : "Tienes {$cantidad} documentos por firmar";

        return match (true) {
            $this->vencido => "Plazo vencido: {$titulo}",
            $this->esRecordatorio => "Recordatorio: {$titulo}",
            default => $titulo,
        };
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
