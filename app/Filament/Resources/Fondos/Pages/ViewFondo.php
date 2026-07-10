<?php

namespace App\Filament\Resources\Fondos\Pages;

use App\Filament\Resources\Fondos\FondoResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewFondo extends ViewRecord
{
    protected static string $resource = FondoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Fondo eliminado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
