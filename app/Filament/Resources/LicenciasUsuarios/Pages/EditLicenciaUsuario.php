<?php

namespace App\Filament\Resources\LicenciasUsuarios\Pages;

use App\Filament\Resources\LicenciasUsuarios\LicenciaUsuarioResource;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Services\Licencias\GestionarCupoLicencia;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditLicenciaUsuario extends EditRecord
{
    protected static string $resource = LicenciaUsuarioResource::class;

    protected function beforeSave(): void
    {
        if (! ($this->data['estado'] ?? false)) {
            return;
        }

        $licencia = Licencia::query()->find($this->data['licencia_id'] ?? $this->record->licencia_id);

        if ($licencia === null) {
            return;
        }

        app(GestionarCupoLicencia::class)->haltarSiNoPuedeConsumir($licencia, $this->record);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos de la licencia del usuario se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (LicenciaUsuario $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (LicenciaUsuario $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (LicenciaUsuario $record): bool => $record->trashed())
                    ->before(fn (LicenciaUsuario $record) => GestionarCupoLicencia::haltarRestoreDeAsignacion($record))
                    ->successNotificationTitle('Asignación restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
