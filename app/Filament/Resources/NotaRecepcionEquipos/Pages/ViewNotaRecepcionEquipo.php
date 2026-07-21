<?php

namespace App\Filament\Resources\NotaRecepcionEquipos\Pages;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use App\Filament\Resources\NotaRecepcionEquipos\NotaRecepcionEquipoResource;
use App\Models\NotaRecepcionEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewNotaRecepcionEquipo extends ViewRecord
{
    protected static string $resource = NotaRecepcionEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...EquipoFormularioPdfActions::make(EquipoFormularioPdfTipo::NotaRecepcion),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (NotaRecepcionEquipo $record): bool => ! $record->trashed()),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
