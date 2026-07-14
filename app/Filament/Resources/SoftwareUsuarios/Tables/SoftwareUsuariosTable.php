<?php

namespace App\Filament\Resources\SoftwareUsuarios\Tables;

use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use App\Filament\Resources\Users\RelationManagers\SoftwareUsuariosRelationManager;
use App\Models\SoftwareUsuario;
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

class SoftwareUsuariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (SoftwareUsuario $record): string => $record->trashed()
                ? SoftwareUsuarioResource::getUrl('view', ['record' => $record])
                : SoftwareUsuarioResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser)
                    ->hiddenOn(SoftwareUsuariosRelationManager::class),
                TextColumn::make('software.nombre_software')
                    ->label('Software')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->sortable()
                    ->boolean(),
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
                SelectFilter::make('id_software')
                    ->label('Software')
                    ->relationship('software', 'nombre_software')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ]),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (SoftwareUsuario $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (SoftwareUsuario $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignacion eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (SoftwareUsuario $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignacion eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (SoftwareUsuario $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignacion restaurada correctamente'),
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
