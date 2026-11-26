<?php

namespace App\Notifications;

use App\Enums\TipoEventoNotificacion;
use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use App\Models\User;
use App\Models\UsuarioExterno;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio para los administradores: el acceso de un usuario externo
 * llegó a su fecha fin y hay que revisar si se retira el acceso compartido.
 * Respeta la preferencia «Usuarios externos» de cada administrador.
 */
class UsuarioExternoFinalizadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public UsuarioExterno $usuarioExterno)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $canales = ['mail', 'database'];

        if (! $notifiable instanceof User) {
            return $canales;
        }

        return array_values(array_filter(
            $canales,
            fn (string $canal): bool => $notifiable->debeRecibir(TipoEventoNotificacion::UsuarioExterno->value, $canal),
        ));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mensaje = (new MailMessage)
            ->subject('[Sistema ICU] '.$this->titulo())
            ->greeting($this->titulo())
            ->line($this->cuerpo())
            ->line("Correo del contacto: {$this->usuarioExterno->correo}")
            ->line("Responsable interno: {$this->responsable()}")
            ->line("Permiso: {$this->usuarioExterno->permiso?->getLabel()}");

        foreach ($this->recursosCompartidos() as $recurso) {
            $mensaje->line($recurso);
        }

        return $mensaje
            ->action('Ver el usuario externo', $this->url())
            ->line('Revisa si corresponde retirar el acceso compartido. Este es un aviso automático del Sistema ICU.');
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
            ->iconColor('warning')
            ->actions([
                Action::make('ver')
                    ->label('Ver el usuario externo')
                    ->button()
                    ->url($this->url())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function titulo(): string
    {
        return "Finalizó el acceso de {$this->usuarioExterno->nombre_contacto} ({$this->usuarioExterno->empresa})";
    }

    private function cuerpo(): string
    {
        $fin = $this->usuarioExterno->fecha_fin?->format('d/m/Y') ?? '—';

        return "El acceso por «{$this->usuarioExterno->motivo}» tenía fecha fin el {$fin}.";
    }

    private function responsable(): string
    {
        return $this->usuarioExterno->responsable?->name ?? '—';
    }

    /**
     * @return list<string>
     */
    private function recursosCompartidos(): array
    {
        return array_values(array_filter([
            $this->usuarioExterno->sitioSharepoint !== null
                ? "SharePoint: {$this->usuarioExterno->sitioSharepoint->nombre_sitio} ({$this->usuarioExterno->sitioSharepoint->url})"
                : null,
            filled($this->usuarioExterno->onedrive) ? "OneDrive: {$this->usuarioExterno->onedrive}" : null,
        ]));
    }

    private function url(): string
    {
        return UsuarioExternoResource::getUrl('view', ['record' => $this->usuarioExterno], panel: 'admin');
    }
}
