<?php

namespace App\Filament\Resources\EntregaCelulars\Pages;

use App\Enums\CelularFormularioPdfTipo;
use App\Filament\Resources\Concerns\CelularFormularioPdfActions;
use App\Filament\Resources\EntregaCelulars\EntregaCelularResource;
use App\Models\EntregaCelular;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewEntregaCelular extends ViewRecord
{
    protected static string $resource = EntregaCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...CelularFormularioPdfActions::make(CelularFormularioPdfTipo::Entrega),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (EntregaCelular $record): bool => ! $record->trashed()),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
