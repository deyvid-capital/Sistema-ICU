<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Enums\EstadoEquipo;
use App\Filament\Concerns\ConfirmsCierreAsignacionEquipo;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use App\Services\CambiarEstadoEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEquipo extends EditRecord
{
    use ConfirmsCierreAsignacionEquipo;

    protected static string $resource = EquipoResource::class;

    /**
     * Estado pendiente de aplicar tras guardar, cuando el cambio cierra una asignación.
     */
    private ?EstadoEquipo $estadoConCierrePendiente = null;

    protected function beforeSave(): void
    {
        $this->estadoConCierrePendiente = null;

        $equipo = $this->getRecord();
        $nuevo = EstadoEquipo::tryFrom((string) ($this->data['estado'] ?? ''));

        if (! $equipo instanceof Equipo || $nuevo === null || $nuevo === $equipo->estado) {
            return;
        }

        if (! app(CambiarEstadoEquipo::class)->requiereConfirmacion($equipo, $nuevo)) {
            return;
        }

        if (! ($this->data['confirmar_cierre_asignacion'] ?? false)) {
            Notification::make()
                ->warning()
                ->title('Confirmación requerida')
                ->body('Este equipo tiene una asignación abierta. Confirme para terminarla antes de cambiar el estado.')
                ->persistent()
                ->send();

            $this->halt();
        }

        $this->estadoConCierrePendiente = $nuevo;
    }

    protected function afterSave(): void
    {
        if ($this->estadoConCierrePendiente === null) {
            return;
        }

        $cerrada = app(CambiarEstadoEquipo::class)->ejecutar(
            $this->getRecord(),
            $this->estadoConCierrePendiente,
        );

        $this->estadoConCierrePendiente = null;
        $this->data['confirmar_cierre_asignacion'] = false;

        if ($cerrada === null) {
            return;
        }

        Notification::make()
            ->success()
            ->title('Asignación terminada')
            ->body('Se terminó la asignación de '.CambiarEstadoEquipo::descripcionAsignacion($cerrada)
                .' con fecha '.$cerrada->fecha_fin->format('d/m/Y').'.')
            ->send();
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Equipo actualizado')
            ->body('Los datos del equipo se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (Equipo $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Equipo eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (Equipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Equipo eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (Equipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Equipo restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
