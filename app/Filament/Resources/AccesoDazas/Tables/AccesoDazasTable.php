<?php

namespace App\Filament\Resources\AccesoDazas\Tables;

use App\Filament\Resources\AccesoDazas\AccesoDazaResource;
use App\Filament\Resources\Users\RelationManagers\AccesosDazaRelationManager;
use App\Models\AccesoDaza;
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

class AccesoDazasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (AccesoDaza $record): string => $record->trashed()
                ? AccesoDazaResource::getUrl('view', ['record' => $record])
                : AccesoDazaResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser)
                    ->hiddenOn(AccesosDazaRelationManager::class),
                TextColumn::make('usuario_daza')
                    ->label('Usuario DAZA')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('nivel_acceso')
                    ->label('Nivel de acceso')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('fondo.nombre_completo')
                    ->label('Fondo')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state, AccesoDaza $record): string => $state ?? $record->fondo?->codigo ?? '—'),
                TextColumn::make('ambiente')
                    ->label('Ambiente')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('fecha')
                    ->label('Fecha')
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
                SelectFilter::make('fondo_id')
                    ->label('Fondo')
                    ->relationship('fondo', 'nombre_completo')
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
                        ->visible(fn (AccesoDaza $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (AccesoDaza $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (AccesoDaza $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (AccesoDaza $record): bool => $record->trashed())
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
