<?php

namespace App\Filament\Resources\Sistemas\Pages;

use App\Filament\Resources\Sistemas\SistemaResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSistema extends ViewRecord
{
    protected static string $resource = SistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Sistema eliminado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
