<?php

namespace App\Filament\Resources\Celulares\Pages;

use App\Filament\Resources\Celulares\CelularResource;
use App\Models\Celular;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCelular extends EditRecord
{
    protected static string $resource = CelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (Celular $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Celular eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (Celular $record): bool => $record->trashed())
                    ->successNotificationTitle('Celular eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (Celular $record): bool => $record->trashed())
                    ->successNotificationTitle('Celular restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
