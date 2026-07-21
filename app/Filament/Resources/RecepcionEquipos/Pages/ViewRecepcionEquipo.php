<?php

namespace App\Filament\Resources\RecepcionEquipos\Pages;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use App\Filament\Resources\RecepcionEquipos\RecepcionEquipoResource;
use App\Models\RecepcionEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewRecepcionEquipo extends ViewRecord
{
    protected static string $resource = RecepcionEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...EquipoFormularioPdfActions::make(EquipoFormularioPdfTipo::Recepcion),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (RecepcionEquipo $record): bool => ! $record->trashed()),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
