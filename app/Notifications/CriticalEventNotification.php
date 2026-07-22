<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de un evento crítico del sistema (asignaciones, eliminaciones,
 * cargadores dañados, excepciones de software, documentos PDF, etc.).
 *
 * Se envía en cola: siempre a la campana del panel (`database`) y, sólo para
 * eventos de severidad alta, también por correo (`mail`).
 */
class CriticalEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const SEVERIDAD_ALTA = 'alta';

    public const SEVERIDAD_MEDIA = 'media';

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public string $icon = 'heroicon-o-bell',
        public string $color = 'warning',
        public string $severity = self::SEVERIDAD_MEDIA,
    ) {}

    /**
     * Canales de entrega. El correo sólo se usa en eventos de severidad alta
     * para no saturar la bandeja de los administradores.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->severity === self::SEVERIDAD_ALTA
            ? ['database', 'mail']
            : ['database'];
    }

    /**
     * Mensaje para la campana del panel (formato de Filament).
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon)
            ->iconColor($this->color)
            ->actions($this->url !== null ? [
                Action::make('ver')
                    ->label('Ver en el sistema')
                    ->button()
                    ->url($this->url, shouldOpenInNewTab: true)
                    ->markAsRead(),
            ] : [])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Sistema ICU] '.$this->title)
            ->greeting($this->title)
            ->line($this->body);

        if ($this->url !== null) {
            $mail->action('Ver en el sistema', $this->url);
        }

        return $mail->line('Este es un aviso automático del Sistema ICU.');
    }
}
