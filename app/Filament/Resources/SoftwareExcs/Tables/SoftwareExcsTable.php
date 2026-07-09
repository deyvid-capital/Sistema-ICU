<?php

namespace App\Filament\Resources\SoftwareExcs\Tables;

use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use App\Models\SoftwareExc;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SoftwareExcsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (SoftwareExc $record): string => $record->trashed()
                ? SoftwareExcResource::getUrl('view', ['record' => $record])
                : SoftwareExcResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha_inicio', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('software.nombre_software')
                    ->label('Software')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tipo_excepcion')
                    ->label('Tipo de excepcion')
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                TextColumn::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->date('d/m/Y')
                    ->placeholder('—')
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
                SelectFilter::make('software_id')
                    ->label('Software')
                    ->relationship('software', 'nombre_software')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tipo_excepcion')
                    ->label('Tipo de excepcion')
                    ->options([
                        'Temporal' => 'Temporal',
                        'Permanente' => 'Permanente',
                        'Emergencia' => 'Emergencia',
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
                        ->visible(fn (SoftwareExc $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (SoftwareExc $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Excepcion eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (SoftwareExc $record): bool => $record->trashed())
                        ->successNotificationTitle('Excepcion eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (SoftwareExc $record): bool => $record->trashed())
                        ->successNotificationTitle('Excepcion restaurada correctamente'),
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
                        ->label('Eliminar seleccionadas')
                        ->successNotificationTitle('Excepciones eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Excepciones eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionadas')
                        ->successNotificationTitle('Excepciones restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
