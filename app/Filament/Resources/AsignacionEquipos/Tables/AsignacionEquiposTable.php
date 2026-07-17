<?php

namespace App\Filament\Resources\AsignacionEquipos\Tables;

use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesUsuarioRelationManager;
use App\Filament\Resources\Users\RelationManagers\AsignacionesEquipoRelationManager;
use App\Models\AsignacionEquipo;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AsignacionEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (AsignacionEquipo $record): string => $record->trashed()
                ? AsignacionEquipoResource::getUrl('view', ['record' => $record])
                : AsignacionEquipoResource::getUrl('edit', ['record' => $record]))
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
                    ->hiddenOn(AsignacionesEquipoRelationManager::class),
                TextColumn::make('equipo.service_tag')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->hiddenOn(AsignacionesUsuarioRelationManager::class),
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
                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->limit(40)
                    ->toggleable()
                    ->placeholder('—'),
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
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AsignacionEquipo $record): bool => ! $record->trashed()),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->successNotificationTitle('Asignaciones de equipo eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Asignaciones de equipo eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Asignaciones de equipo restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
