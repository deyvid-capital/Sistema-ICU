<?php

namespace App\Jobs;

use App\Enums\UsuarioExternoFormularioPdfTipo;
use App\Exceptions\UsuarioExternoFormularioPdfException;
use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use App\Models\User;
use App\Models\UsuarioExterno;
use App\Notifications\CriticalEventNotification;
use App\Services\Pdf\GenerateUsuarioExternoFormularioPdf;
use App\Support\Notifications\NotifyAdmins;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Genera de forma asíncrona el PDF oficial del Formulario de Usuario Externo
 * usando Browsershot y notifica cuando el documento está listo.
 */
class GenerateUsuarioExternoFormularioPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Browsershot usa un timeout interno de 120 s; damos algo de margen.
     */
    public int $timeout = 180;

    public int $tries = 2;

    public function __construct(
        public UsuarioExterno $record,
        public UsuarioExternoFormularioPdfTipo $tipo = UsuarioExternoFormularioPdfTipo::Formulario,
        public ?string $actorId = null,
        public bool $reemplazarFirmado = false,
    ) {}

    public function handle(GenerateUsuarioExternoFormularioPdf $service): void
    {
        try {
            $service->execute($this->record, $this->reemplazarFirmado);
        } catch (UsuarioExternoFormularioPdfException $exception) {
            $this->fail($exception);

            return;
        }

        $url = UsuarioExternoResource::getUrl('view', ['record' => $this->record]);
        $actor = $this->actor();

        if ($actor !== null) {
            $actor->notify(new CriticalEventNotification(
                title: 'PDF listo: '.$this->tipo->nombreOficial(),
                body: 'Se generó «'.$this->tipo->nombreOficial().'» que solicitaste.',
                url: $url,
                icon: 'heroicon-o-document-arrow-down',
                color: 'success',
                severity: CriticalEventNotification::SEVERIDAD_MEDIA,
            ));
        }

        NotifyAdmins::dispatch(
            title: 'PDF listo: '.$this->tipo->nombreOficial(),
            body: 'Se generó «'.$this->tipo->nombreOficial().'»'.$this->requeridoPor().'.',
            url: $url,
            icon: 'heroicon-o-document-arrow-down',
            color: 'success',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
            actor: $actor,
        );
    }

    public function failed(Throwable $exception): void
    {
        $notification = new CriticalEventNotification(
            title: 'No se pudo generar el PDF',
            body: 'Error al generar «'.$this->tipo->nombreOficial().'»: '.$exception->getMessage(),
            icon: 'heroicon-o-x-circle',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );

        $actor = $this->actor();

        if ($actor !== null) {
            $actor->notify($notification);

            return;
        }

        NotifyAdmins::dispatch(
            title: 'No se pudo generar el PDF',
            body: 'Error al generar «'.$this->tipo->nombreOficial().'»: '.$exception->getMessage(),
            icon: 'heroicon-o-x-circle',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    private function actor(): ?User
    {
        return $this->actorId !== null ? User::find($this->actorId) : null;
    }

    private function requeridoPor(): string
    {
        return $this->actor() !== null ? ' (solicitado por '.$this->actor()->name.')' : '';
    }
}
