<?php

namespace App\Filament\Resources\SitioSharepoints\Pages;

use App\Filament\Resources\SitioSharepoints\SitioSharepointResource;
use App\Models\AccesoSharepoint;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSitioSharepoint extends EditRecord
{
    protected static string $resource = SitioSharepointResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Sitio actualizado')
            ->body('Los cambios del sitio SharePoint se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Sitio eliminado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }

    protected function afterSave(): void
    {
        if (! $this->record->estado) {
            $accesos = AccesoSharepoint::where('sitio_id', $this->record->id)->where('estado', true)->get();

            foreach ($accesos as $acceso) {
                $acceso->update(['estado' => false]);
            }
        }
    }
}
