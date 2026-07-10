<?php

namespace App\Filament\Resources\AccesoSistemas\Tables;

use App\Filament\Resources\AccesoSistemas\AccesoSistemaResource;
use App\Filament\Resources\Users\RelationManagers\AccesosSistemasRelationManager;
use App\Models\AccesoSistema;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccesoSistemasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (AccesoSistema $record): string => $record->trashed()
                ? AccesoSistemaResource::getUrl('view', ['record' => $record])
                : AccesoSistemaResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha_inicio', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser)
                    ->hiddenOn(AccesosSistemasRelationManager::class),
                TextColumn::make('sistema.nombre_sistema')
                    ->label('Sistema')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('usuario_sistema')
                    ->label('Usuario del sistema')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('nivel_acceso')
                    ->label('Nivel de acceso')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
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
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('id_sistema')
                    ->label('Sistema')
                    ->relationship('sistema', 'nombre_sistema')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        $estado = $data['value'] ?? null;

                        if ($estado === null || $estado === '') {
                            return $query;
                        }

                        return $query->where('estado', (bool) (int) $estado);
                    }),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AccesoSistema $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (AccesoSistema $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (AccesoSistema $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (AccesoSistema $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación restaurada correctamente'),
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
                        ->successNotificationTitle('Asignaciones eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Asignaciones eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Asignaciones restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
