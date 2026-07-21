<?php

namespace App\Filament\Resources\NotaSalidaEquipos\Pages;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use App\Filament\Resources\NotaSalidaEquipos\NotaSalidaEquipoResource;
use App\Models\NotaSalidaEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewNotaSalidaEquipo extends ViewRecord
{
    protected static string $resource = NotaSalidaEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...EquipoFormularioPdfActions::make(EquipoFormularioPdfTipo::NotaSalida),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (NotaSalidaEquipo $record): bool => ! $record->trashed()),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
