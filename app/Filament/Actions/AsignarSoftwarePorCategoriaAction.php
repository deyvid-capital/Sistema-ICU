<?php

namespace App\Filament\Actions;

use App\Models\CategoriaSoftware;
use App\Models\User;
use App\Services\Software\AsignarSoftwareEnBloque;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Asigna al usuario dueño del gestor de relaciones todo el software activo de
 * una categoría del catálogo.
 */
class AsignarSoftwarePorCategoriaAction
{
    public static function make(RelationManager $relationManager): Action
    {
        return Action::make('asignarPorCategoria')
            ->label('Asignar por categoría')
            ->icon(Heroicon::OutlinedTag)
            ->color('success')
            ->modalHeading('Asignar software por categoría')
            ->modalDescription('Se asigna al usuario todo el software activo de la categoría elegida. Lo que ya tiene asignado no cambia.')
            ->modalSubmitActionLabel('Asignar')
            ->schema([
                Select::make('categoria_id')
                    ->label('Categoría de software')
                    ->options(fn (): array => CategoriaSoftware::query()
                        ->whereHas('software', fn (Builder $query): Builder => $query->where('estado', true))
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id_categoria')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data) use ($relationManager): void {
                /** @var User $usuario */
                $usuario = $relationManager->getOwnerRecord();
                $categoria = CategoriaSoftware::query()->findOrFail($data['categoria_id']);

                $asignados = app(AsignarSoftwareEnBloque::class)->porCategoria($usuario, $categoria);

                Notification::make()
                    ->success()
                    ->title('Asignación por categoría completada')
                    ->body("Se asignaron {$asignados} software(s) de «{$categoria->nombre}» al usuario.")
                    ->send();
            });
    }
}
