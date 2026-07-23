<?php

namespace App\Filament\Resources\RecepcionCelulars\Pages;

use App\Enums\CelularFormularioPdfTipo;
use App\Filament\Resources\Concerns\CelularFormularioPdfActions;
use App\Filament\Resources\RecepcionCelulars\RecepcionCelularResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditRecepcionCelular extends EditRecord
{
    protected static string $resource = RecepcionCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...CelularFormularioPdfActions::make(CelularFormularioPdfTipo::Recepcion),
                ViewAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
