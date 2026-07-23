<?php

namespace App\Filament\Resources\RecepcionCelulars\Pages;

use App\Enums\CelularFormularioPdfTipo;
use App\Filament\Resources\Concerns\CelularFormularioPdfActions;
use App\Filament\Resources\RecepcionCelulars\RecepcionCelularResource;
use App\Models\RecepcionCelular;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewRecepcionCelular extends ViewRecord
{
    protected static string $resource = RecepcionCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...CelularFormularioPdfActions::make(CelularFormularioPdfTipo::Recepcion),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (RecepcionCelular $record): bool => ! $record->trashed()),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
