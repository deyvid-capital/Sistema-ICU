<?php

namespace App\Filament\Resources\SolicitudesFirma\Pages;

use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirma;
use App\Services\Firma\EliminarBorradorSolicitudFirma;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class ViewSolicitudFirma extends ViewRecord
{
    protected static string $resource = SolicitudFirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ubicarFirmas')
                ->label('Ubicar firmas y enviar')
                ->icon(Heroicon::OutlinedCursorArrowRays)
                ->visible(fn (): bool => Gate::allows('prepararEnvio', $this->getRecord()))
                ->url(fn (): string => SolicitudFirmaResource::getUrl('posiciones', ['record' => $this->getRecord()])),
            DeleteAction::make()
                ->label('Eliminar borrador')
                ->icon(Heroicon::OutlinedTrash)
                ->modalHeading('Eliminar borrador')
                ->modalDescription('Se eliminan el envío y sus copias de los documentos. Los documentos originales del sistema no se tocan.')
                ->using(function (SolicitudFirma $record): bool {
                    app(EliminarBorradorSolicitudFirma::class)($record);

                    return true;
                })
                ->successNotificationTitle('Borrador eliminado'),
        ];
    }
}
