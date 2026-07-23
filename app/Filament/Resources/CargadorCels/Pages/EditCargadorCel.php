<?php

namespace App\Filament\Resources\CargadorCels\Pages;

use App\Filament\Resources\CargadorCels\CargadorCelResource;
use App\Models\CargadorCel;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCargadorCel extends EditRecord
{
    protected static string $resource = CargadorCelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (CargadorCel $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Cargador de celular eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (CargadorCel $record): bool => $record->trashed())
                    ->successNotificationTitle('Cargador de celular eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (CargadorCel $record): bool => $record->trashed())
                    ->successNotificationTitle('Cargador de celular restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
