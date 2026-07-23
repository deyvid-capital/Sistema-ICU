<?php

namespace App\Filament\Resources\PdfCelulars\Pages;

use App\Filament\Resources\PdfCelulars\Actions\PdfCelularDocumentActions;
use App\Filament\Resources\PdfCelulars\PdfCelularResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPdfCelular extends ViewRecord
{
    protected static string $resource = PdfCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                PdfCelularDocumentActions::regenerate(),
                ...PdfCelularDocumentActions::make(),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->successNotificationTitle('PDF eliminado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('primary')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
