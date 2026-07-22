<?php

namespace App\Jobs;

use App\Enums\EquipoFormularioPdfTipo;
use App\Exceptions\EquipoFormularioPdfException;
use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Services\Pdf\GenerateEquipoFormularioPdf;
use App\Support\Notifications\NotifyAdmins;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Genera de forma asíncrona el PDF oficial de un formulario de equipo
 * (entrega, recepción, nota de salida/recepción) usando Browsershot y
 * notifica a los administradores cuando el documento está listo.
 */
class GenerateEquipoFormularioPdfJob implements ShouldQueue
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
        public Model $record,
        public EquipoFormularioPdfTipo $tipo,
        public ?string $actorId = null,
    ) {}

    public function handle(GenerateEquipoFormularioPdf $service): void
    {
        try {
            $pdf = $service->execute($this->record, $this->tipo);
        } catch (EquipoFormularioPdfException $exception) {
            // Error de validación/datos: reintentar no ayuda.
            $this->fail($exception);

            return;
        }

        NotifyAdmins::dispatch(
            title: 'PDF listo: '.$this->tipo->nombreOficial(),
            body: 'Se generó «'.$this->tipo->nombreOficial().'»'.$this->requeridoPor().'.',
            url: PdfEquipoResource::getUrl('view', ['record' => $pdf]),
            icon: 'heroicon-o-document-arrow-down',
            color: 'success',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
            actor: $this->actor(),
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
