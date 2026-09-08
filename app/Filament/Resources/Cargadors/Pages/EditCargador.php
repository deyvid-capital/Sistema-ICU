<?php

namespace App\Filament\Resources\Cargadors\Pages;

use App\Enums\EstadoCargador;
use App\Filament\Concerns\ConfirmsCierreAsignacionCargador;
use App\Filament\Resources\Cargadors\CargadorResource;
use App\Models\Cargador;
use App\Services\CambiarEstadoCargador;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCargador extends EditRecord
{
    use ConfirmsCierreAsignacionCargador;

    protected static string $resource = CargadorResource::class;

    private ?EstadoCargador $estadoConCierrePendiente = null;

    protected function beforeSave(): void
    {
        $this->estadoConCierrePendiente = null;

        $cargador = $this->getRecord();
        $nuevo = EstadoCargador::tryFrom((string) ($this->data['estado'] ?? ''));

        if (! $cargador instanceof Cargador || $nuevo === null || $nuevo === $cargador->estado) {
            return;
        }

        if (! $nuevo->esSeleccionableManualmente()) {
            Notification::make()
                ->danger()
                ->title('Estado no permitido')
                ->body("«{$nuevo->getLabel()}» se asigna al asignar el cargador y no puede elegirse a mano.")
                ->persistent()
                ->send();

            $this->halt();
        }

        if (! app(CambiarEstadoCargador::class)->requiereConfirmacion($cargador, $nuevo)) {
            return;
        }

        if (! ($this->data['confirmar_cierre_asignacion'] ?? false)) {
            Notification::make()
                ->warning()
                ->title('Confirmación requerida')
                ->body('Este cargador está asignado a un equipo. Confirme para terminar esa asignación antes de cambiar el estado.')
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

        $cerrada = app(CambiarEstadoCargador::class)->ejecutar(
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
            ->body('Se cerró la asignación del equipo '.($cerrada->service_tag)
                .' con fecha de baja '.$cerrada->fecha_fin->format('d/m/Y').'.')
            ->send();
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cargador actualizado')
            ->body('Los datos del cargador se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (Cargador $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Cargador eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (Cargador $record): bool => $record->trashed())
                    ->successNotificationTitle('Cargador eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (Cargador $record): bool => $record->trashed())
                    ->successNotificationTitle('Cargador restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
