<?php

namespace App\Filament\Resources\Licencias\Pages;

use App\Filament\Resources\Licencias\LicenciaResource;
use App\Models\LicenciaUsuario;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditLicencia extends EditRecord
{
    protected static string $resource = LicenciaResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Licencia actualizada')
            ->body('Los cambios de la licencia se guardaron correctamente.');
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
                    ->successNotificationTitle('Licencia eliminada correctamente'),
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
            $softwareUsuarios = LicenciaUsuario::where('licencia_id', $this->record->id)->where('estado', true)->get();
            foreach ($softwareUsuarios as $softwareUsuario) {
                $softwareUsuario->update(['estado' => false]);
            }
        }
    }
}
