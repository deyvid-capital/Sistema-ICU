<?php

namespace App\Filament\Resources\RecepcionEquipos\Tables;

use App\Filament\Resources\RecepcionEquipos\RecepcionEquipoResource;
use App\Models\RecepcionEquipo;
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

class RecepcionEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (RecepcionEquipo $record): string => $record->trashed()
                ? RecepcionEquipoResource::getUrl('view', ['record' => $record])
                : RecepcionEquipoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('asignacion.service_tag')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->placeholder('—'),
                TextColumn::make('asignacion.usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
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
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (RecepcionEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (RecepcionEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Formulario de recepción eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (RecepcionEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Formulario de recepción eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (RecepcionEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Formulario de recepción restaurado correctamente'),
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
                        ->successNotificationTitle('Formularios de recepción eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Formularios de recepción eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Formularios de recepción restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
