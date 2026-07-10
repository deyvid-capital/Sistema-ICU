<?php

namespace App\Filament\Resources\Sistemas\Tables;

use App\Filament\Resources\Sistemas\SistemaResource;
use App\Models\Sistema;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SistemasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Sistema $record): string => SistemaResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('nombre_sistema')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('nombre_sistema')
                    ->label('Nombre del sistema')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedServerStack),
                TextColumn::make('accesos_count')
                    ->label('Accesos')
                    ->counts('accesos')
                    ->badge()
                    ->color('info'),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->successNotificationTitle('Sistema eliminado correctamente'),
                ])
                    ->label('Acciones')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('info')
                    ->button()
                    ->tooltip('Acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->successNotificationTitle('Sistemas eliminados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
