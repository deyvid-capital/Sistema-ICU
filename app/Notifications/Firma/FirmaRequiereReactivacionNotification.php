<?php

namespace App\Notifications\Firma;

use App\Filament\Pages\MiFirmaDigital;
use App\Filament\Resources\CertificadosFirma\CertificadoFirmaResource;
use App\Models\User;
use App\Notifications\Firma\Concerns\RespetaPreferenciaFirma;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * La contraseña del sistema se restableció sin pasar por Mi perfil y la firma quedó
 * en pausa. Una versión para el titular (cómo reactivarla) y otra para los admins.
 * Con copia en custodia la reactiva el titular al iniciar sesión; sin ella, el admin. Por campana y correo, según la preferencia "Firma digital".
 */
class FirmaRequiereReactivacionNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespetaPreferenciaFirma;

    public function __construct(
        public bool $paraAdministrador,
        public string $motivo,
        public string $fecha,
        public ?string $titularNombre = null,
        public ?string $titularCarnet = null,
        public bool $reactivaElTitular = true,
    ) {
        $this->afterCommit();
    }

    /**
     * @param  bool  $reactivaElTitular  False si la firma no tiene copia en custodia y debe reactivarla el admin.
     */
    public static function paraTitular(string $motivo, bool $reactivaElTitular = true): self
    {
        return new self(false, $motivo, self::ahora(), reactivaElTitular: $reactivaElTitular);
    }

    public static function paraAdministrador(User $titular, string $motivo, bool $reactivaElTitular = true): self
    {
        return new self(true, $motivo, self::ahora(), $titular->name, $titular->carnet, $reactivaElTitular);
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
            ->body($this->resumen())
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('warning')
            ->actions([
                Action::make('ver')
                    ->label($this->paraAdministrador ? 'Ver firmas digitales' : 'Ver mi firma digital')
                    ->button()
                    ->url($this->url())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $correo = (new MailMessage)
            ->subject('[Sistema ICU] '.$this->titulo())
            ->greeting($this->titulo());

        if ($this->paraAdministrador) {
            return $correo
                ->line("**Usuario:** {$this->titularNombre}".($this->titularCarnet ? " (CI {$this->titularCarnet})" : ''))
                ->line("**Motivo:** contraseña {$this->motivo}.")
                ->line("**Fecha:** {$this->fecha}.")
                ->line($this->reactivaElTitular
                    ? '**Qué hacer:** nada. La firma se reactiva sola cuando el usuario inicie sesión con su nueva contraseña.'
                    : '**Qué hacer:** esta firma se activó antes del cambio de flujo y no tiene la contraseña del .p12 en custodia. Entra a *Configuración → Firmas digitales → Reactivar firma* y escribe la contraseña del .p12.')
                ->action('Ver firmas digitales', $this->url())
                ->line('Este es un aviso automático del Sistema ICU.');
        }

        $correo = $correo
            ->line("Tu contraseña del sistema fue restablecida el {$this->fecha} ({$this->motivo}).")
            ->line('Por seguridad, tu firma digital quedó en pausa.');

        if (! $this->reactivaElTitular) {
            return $correo
                ->line('Un administrador debe reactivarla. Ya se le avisó; no tienes que hacer nada más.')
                ->action('Ver mi firma digital', $this->url())
                ->line('Este es un aviso automático del Sistema ICU.');
        }

        return $correo
            ->line('**Para reactivarla** solo inicia sesión con tu **nueva contraseña**: la firma se vuelve a vincular sola y desde ese momento firmas con ella.')
            ->line('Si ya tienes la sesión abierta, ve a **Menú de usuario → Mi firma digital → Reactivar mi firma** y escribe tu nueva contraseña del sistema.')
            ->action('Ver mi firma digital', $this->url())
            ->line('Este es un aviso automático del Sistema ICU.');
    }

    private function resumen(): string
    {
        return match (true) {
            $this->paraAdministrador && $this->reactivaElTitular => "Motivo: {$this->motivo}. Se reactiva sola cuando el usuario inicie sesión con su nueva contraseña.",
            $this->paraAdministrador => "Motivo: {$this->motivo}. No tiene la contraseña del .p12 en custodia: reactívala escribiendo la contraseña del .p12.",
            $this->reactivaElTitular => "Tu contraseña del sistema fue restablecida ({$this->motivo}). Tu firma se reactiva sola al iniciar sesión con tu nueva contraseña.",
            default => "Tu contraseña del sistema fue restablecida ({$this->motivo}). Un administrador debe reactivar tu firma.",
        };
    }

    private function titulo(): string
    {
        return $this->paraAdministrador
            ? "Firma digital de {$this->titularNombre} requiere reactivación"
            : 'Tu firma digital quedó en pausa';
    }

    private function url(): string
    {
        return $this->paraAdministrador
            ? CertificadoFirmaResource::getUrl(panel: 'admin')
            : MiFirmaDigital::getUrl(panel: 'admin');
    }

    private static function ahora(): string
    {
        return now()->timezone(config('app.display_timezone'))->format('d/m/Y H:i');
    }
}
