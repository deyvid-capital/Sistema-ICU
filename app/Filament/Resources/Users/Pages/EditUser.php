<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Movimiento;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $navigationLabel = 'Editar';

    protected static ?int $navigationSort = 2;

    protected function getAllRelationManagers(): array
    {
        return [];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Usuario actualizado')
            ->body("Los datos de {$this->record->name} se guardaron correctamente.");
    }

    /**
     * Refleja el estado actual del usuario (Alta/Baja) en el toggle al abrir el formulario.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        
        $data['movimientoActual'] = $this->record->estaDeAlta();
        return $data;
    }

    /**
     * Valida el cambio de estado (Alta/Baja) con las mismas reglas del historial
     * de movimientos antes de guardar el usuario.
     */
    protected function beforeSave(): void
    {
        
            $movimiento = $this->movimientoPendiente();
        
            if ($movimiento === null) {
                return;
            }

            $errores = Movimiento::erroresDeValidacion($movimiento);

            if ($errores !== []) {
                Notification::make()
                    ->title('No se pudo cambiar el estado del usuario')
                    ->body(implode(' ', $errores))
                    ->danger()
                    ->send();

                $this->halt();
          }
        
    }

    protected function afterSave(): void
    {
        $movimiento = $this->movimientoPendiente();

        // Solo registra un movimiento cuando el estado realmente cambió.
        if ($movimiento !== null) {
            $this->record->movimientos()->create($movimiento);
        }
    }

    /**
     * Datos del movimiento que generaría el cambio de estado del toggle,
     * o null si el estado no cambió respecto al último movimiento.
     *
     * @return array{usuario_id:string,tipo:string,fecha:Carbon,cargo_id:int|null}|null
     */
    protected function movimientoPendiente(): ?array
    {
        $tipo = ($this->data['movimientoActual'] ?? false) ? 'Alta' : 'Baja';
        $ultimo = $this->record->ultimoMovimiento;

        if ($ultimo?->tipo === $tipo) {
            return null;
        }

        // Una Baja debe cerrar con el mismo cargo de la Alta que cierra.
        $cargoId = $tipo === 'Baja' ? $ultimo?->cargo_id : $this->record->id_cargo;

        return [
            'usuario_id' => $this->record->id,
            'tipo' => $tipo,
            'fecha' => now(),
            'cargo_id' => $cargoId,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (User $record): bool => ! $record->trashed()
                                    && $record->id !== auth()->user()->id)
                    ->successNotificationTitle('Usuario eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->successNotificationTitle('Usuario eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->successNotificationTitle('Usuario restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
