<?php

namespace App\Filament\Resources\NotaSalidaEquipos\Pages;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use App\Filament\Resources\NotaSalidaEquipos\NotaSalidaEquipoResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditNotaSalidaEquipo extends EditRecord
{
    protected static string $resource = NotaSalidaEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...EquipoFormularioPdfActions::make(EquipoFormularioPdfTipo::NotaSalida),
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
