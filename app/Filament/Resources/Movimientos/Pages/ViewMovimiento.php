<?php

namespace App\Filament\Resources\Movimientos\Pages;

use App\Filament\Resources\Concerns\MovimientoFormularioPdfActions;
use App\Filament\Resources\Movimientos\MovimientoResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewMovimiento extends ViewRecord
{
    protected static string $resource = MovimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...MovimientoFormularioPdfActions::make(),
                EditAction::make()
                    ->label('Editar movimiento'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
