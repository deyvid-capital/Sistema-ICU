<?php

namespace App\Filament\Resources\AsignacionEquipos\Pages;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoWorkflow;
use App\Filament\Resources\Concerns\DispatchesEquipoFormularioPdfAfterPersist;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use App\Models\AsignacionEquipo;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EditAsignacionEquipo extends EditRecord
{
    use DispatchesEquipoFormularioPdfAfterPersist;

    protected static string $resource = AsignacionEquipoResource::class;

    protected function equipoFormularioPdfTipo(): EquipoFormularioPdfTipo
    {
        return EquipoFormularioPdfTipo::Entrega;
    }

    protected function formularioPdfRecord(): ?Model
    {
        /** @var AsignacionEquipo $asignacion */
        $asignacion = $this->record;

        return $asignacion->entrega;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos de la asignación de equipo se guardaron correctamente.');
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->saveAndGeneratePdfFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...EquipoFormularioPdfActions::makeForAsignacionEntrega(),
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AsignacionEquipo $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación de equipo eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AsignacionEquipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de equipo eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AsignacionEquipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de equipo restaurada correctamente'),
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
        AsignacionEquipoWorkflow::actualizarEstadoEquipo(
            $this->record->service_tag,
            cerrada: filled($this->record->fecha_fin),
        );

        $this->record->load('entrega');

        $this->dispatchEquipoFormularioPdfIfRequested();
    }
}
