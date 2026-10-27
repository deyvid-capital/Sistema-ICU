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
 * en pausa. Una versión para el titular (cómo reactivarla) y otra para los admins
 * (a quién guiar). Por campana y correo, según la preferencia "Firma digital".
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
    ) {
        $this->afterCommit();
    }

    public static function paraTitular(string $motivo): self
    {
        return new self(false, $motivo, self::ahora());
    }

    public static function paraAdministrador(User $titular, string $motivo): self
    {
        return new self(true, $motivo, self::ahora(), $titular->name, $titular->carnet);
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
            ->body($this->paraAdministrador
                ? "Motivo: {$this->motivo}. El usuario ya recibió las instrucciones para reactivarla."
                : "Tu contraseña del sistema fue restablecida ({$this->motivo}). Reactiva tu firma para volver a firmar.")
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('warning')
            ->actions([
                Action::make('ver')
                    ->label($this->paraAdministrador ? 'Ver firmas digitales' : 'Reactivar mi firma')
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
                ->line('**Qué hacer:** el usuario ya recibió las instrucciones. Si necesita ayuda, debe entrar a *Mi firma digital → Reactivar* con la contraseña de su archivo .p12 y su nueva contraseña del sistema.')
                ->line('Si perdió la contraseña del .p12, hay que solicitar la reposición a Digicert y subir el archivo nuevo en *Configuración → Firmas digitales → Renovar*.')
                ->action('Ver firmas digitales', $this->url())
                ->line('Este es un aviso automático del Sistema ICU.');
        }

        return $correo
            ->line("Tu contraseña del sistema fue restablecida el {$this->fecha} ({$this->motivo}).")
            ->line('Por seguridad, tu firma digital quedó en pausa hasta que la reactives.')
            ->line('**Cómo reactivarla (2 minutos):**')
            ->line('1. Entra al sistema con tu nueva contraseña.')
            ->line('2. Ve a **Menú de usuario → Mi firma digital → Reactivar**.')
            ->line('3. Paso 1: escribe la contraseña de tu archivo **.p12** (la de Digicert/Viafirma, **no** la del sistema).')
            ->line('4. Paso 2: confirma tu **nueva contraseña del sistema**.')
            ->action('Reactivar mi firma', $this->url())
            ->line('Si no recuerdas la contraseña del .p12, comunícate con el administrador.');
    }

    private function titulo(): string
    {
        return $this->paraAdministrador
            ? "Firma digital de {$this->titularNombre} requiere reactivación"
            : 'Debes reactivar tu firma digital';
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
