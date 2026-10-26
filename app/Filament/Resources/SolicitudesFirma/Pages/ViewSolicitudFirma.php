<?php

namespace App\Filament\Resources\SolicitudesFirma\Pages;

use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirma;
use App\Services\Firma\EliminarBorradorSolicitudFirma;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSolicitudFirma extends ViewRecord
{
    protected static string $resource = SolicitudFirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
