<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\EquipoFormularioPdfTipo;
use App\Jobs\GenerateEquipoFormularioPdfJob;
use App\Models\Concerns\HasDocumentoOficialPdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

trait DispatchesEquipoFormularioPdfAfterPersist
{
    public bool $generatePdfAfterPersist = false;

    abstract protected function equipoFormularioPdfTipo(): EquipoFormularioPdfTipo;

    /**
     * Modelo sobre el que se genera el PDF (p. ej. la entrega de una asignación).
     */
    protected function formularioPdfRecord(): ?Model
    {
        return $this->record;
    }

    protected function createAndGeneratePdfFormAction(): Action
    {
        return Action::make('createAndGeneratePdf')
            ->label('Crear y generar formulario PDF')
            ->color('success')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->action('createAndGeneratePdf');
    }

    protected function saveAndGeneratePdfFormAction(): Action
    {
        return Action::make('saveAndGeneratePdf')
            ->label(fn (): string => $this->recordHasDocumentoOficial()
                ? 'Volver a generar PDF'
                : 'Guardar y generar PDF')
            ->color('success')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->action('saveAndGeneratePdf');
    }

    public function createAndGeneratePdf(): void
    {
        $this->generatePdfAfterPersist = true;

        try {
            $this->create();
        } finally {
            $this->generatePdfAfterPersist = false;
        }
    }

    public function saveAndGeneratePdf(): void
    {
        $this->generatePdfAfterPersist = true;

        try {
            $this->save();
        } finally {
            $this->generatePdfAfterPersist = false;
        }
    }

    protected function dispatchEquipoFormularioPdfIfRequested(): void
    {
        if (! $this->generatePdfAfterPersist) {
            return;
        }

        $record = $this->formularioPdfRecord();

        if ($record === null) {
            Notification::make()
                ->warning()
                ->title('Sin formulario de entrega')
                ->body('Complete la pestaña Entrega (fecha) para poder generar el PDF.')
                ->send();

            return;
        }

        GenerateEquipoFormularioPdfJob::dispatch(
            $record,
            $this->equipoFormularioPdfTipo(),
            auth()->id(),
        );

        $nombre = $this->equipoFormularioPdfTipo()->nombreOficial();

        Notification::make()
            ->title('Generando PDF…')
            ->body('El documento «'.$nombre.'» se está generando en segundo plano. Se te avisará cuando esté listo.')
            ->info()
            ->send();
    }

    protected function recordHasDocumentoOficial(): bool
    {
        $record = $this->formularioPdfRecord();

        if (! $record instanceof Model) {
            return false;
        }

        if (! in_array(HasDocumentoOficialPdf::class, class_uses_recursive($record), true)) {
            return false;
        }

        /** @var HasDocumentoOficialPdf&Model $record */
        return $record->hasDocumentoOficial();
    }
}
