<?php

namespace App\Filament\Resources\PdfEquipos\Pages;

use App\Filament\Resources\PdfEquipos\Actions\PdfEquipoDocumentActions;
use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use App\Models\PdfEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPdfEquipo extends ViewRecord
{
    protected static string $resource = PdfEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                PdfEquipoDocumentActions::regenerate(),
                ...PdfEquipoDocumentActions::make(),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (PdfEquipo $record): bool => ! $record->trashed())
                    ->successNotification(
                        fn (PdfEquipo $record): Notification => PdfEquipoResource::getDeletedNotification($record),
                    ),
            ])->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('primary')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
