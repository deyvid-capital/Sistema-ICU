<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activitylogs\Pages;

use App\Filament\Resources\Activitylogs\ActivitylogResource;
use App\Support\Auditoria\ExportadorActividadCsv;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListActivitylogs extends ListRecords
{
    protected static string $resource = ActivitylogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportarCsvAction(),
        ];
    }

    /**
     * Descarga inmediata, sin pasar por la cola: el CSV se emite mientras se
     * leen las filas. Exporta lo que la tabla está mostrando, así que sin
     * filtros aplicados baja el registro completo.
     */
    protected function exportarCsvAction(): Action
    {
        return Action::make('exportarCsv')
            ->label('Exportar CSV')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->action(function (): ?StreamedResponse {
                $consulta = $this->getFilteredSortedTableQuery();

                if ($consulta === null || ! $consulta->clone()->exists()) {
                    Notification::make()
                        ->warning()
                        ->title('No hay registros que exportar')
                        ->body('Ajusta los filtros e inténtalo de nuevo.')
                        ->send();

                    return null;
                }

                return app(ExportadorActividadCsv::class)->descargar($consulta);
            });
    }
}
