<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

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
        $ultimoTipo = $this->record->movimientos()->latest('fecha')->value('tipo');

        $data['tipo'] = $ultimoTipo !== 'Baja';

        return $data;
    }

    protected function afterSave(): void
    {
        $tipo = ($this->data['tipo'] ?? false) ? 'Alta' : 'Baja';

        $ultimoTipo = $this->record->movimientos()->latest('fecha')->value('tipo');

        // Solo registra un movimiento cuando el estado realmente cambió.
        if ($ultimoTipo !== $tipo) {
            $this->record->movimientos()->create([
                'tipo' => $tipo,
                'fecha' => now(),
                'cargo_id' => $this->record->id_cargo,
            ]);
        }
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
