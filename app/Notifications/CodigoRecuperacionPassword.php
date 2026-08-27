<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class CodigoRecuperacionPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $codigo,
        public int $minutos,
        public Carbon $expiraEn,
        public string $urlRestablecer,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $notifiable->name ?? 'hola';
        $codigoFormateado = strlen($this->codigo) === 8
            ? substr($this->codigo, 0, 4).'-'.substr($this->codigo, 4)
            : $this->codigo;
        $expira = $this->expiraEn
            ->timezone(config('app.display_timezone'))
            ->format('H:i');

        return (new MailMessage)
            ->subject('[Sistema ICU] Tu código para restablecer la contraseña')
            ->markdown('mail.recuperacion-password', [
                'nombre' => $nombre,
                'codigo' => $codigoFormateado,
                'minutos' => $this->minutos,
                'expira' => $expira,
                'url' => $this->urlRestablecer,
            ]);
    }
}
