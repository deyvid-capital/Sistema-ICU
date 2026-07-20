<?php

namespace App\Filament\Resources\ReciboEquipos\Pages;

use App\Filament\Resources\ReciboEquipos\Actions\ReciboEquipoDocumentActions;
use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use App\Models\ReciboEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewReciboEquipo extends ViewRecord
{
    protected static string $resource = ReciboEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...ReciboEquipoDocumentActions::make(),
                EditAction::make()
                    ->label('Editar recibo')
                    ->visible(fn (ReciboEquipo $record): bool => ! $record->trashed()),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
