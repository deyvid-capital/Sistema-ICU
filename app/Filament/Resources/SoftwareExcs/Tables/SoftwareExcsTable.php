<?php

namespace App\Filament\Resources\SoftwareExcs\Tables;

use App\Enums\EstadoAprobacionExcepcion;
use App\Filament\Actions\AprobarExcepcionSoftwareAction;
use App\Filament\Actions\RechazarExcepcionSoftwareAction;
use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use App\Filament\Resources\Users\RelationManagers\SoftwareExcepcionesRelationManager;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SoftwareExcsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(function (SoftwareExc $record): string {
                if ($record->trashed() || $record->estaPendiente() || ! SoftwareExcResource::canEdit($record)) {
                    return SoftwareExcResource::getUrl('view', ['record' => $record]);
                }

                return SoftwareExcResource::getUrl('edit', ['record' => $record]);
            })
            ->defaultSort('fecha_inicio', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser)
                    ->sortable()
                    ->searchable()
                    ->hiddenOn(SoftwareExcepcionesRelationManager::class),
                TextColumn::make('software.nombre_software')
                    ->label('Software')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tipo_excepcion')
                    ->label('Tipo de excepcion')
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                TextColumn::make('estado_aprobacion')
                    ->label('Aprobación')
                    ->badge()
                    ->sortable(),
                TextColumn::make('areaAprobadora.area')
                    ->label('Área que aprueba')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('aprobador.name')
                    ->label('Aprobador')
                    ->toggleable()
                    ->sortable()
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
                SelectFilter::make('estado_aprobacion')
                    ->label('Estado de aprobación')
                    ->options(EstadoAprobacionExcepcion::class),
                Filter::make('pendientes_mios')
                    ->label('Pendientes de mi aprobación')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->where('aprobador_id', auth()->id())
                        ->where('estado_aprobacion', EstadoAprobacionExcepcion::Pendiente)),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    AprobarExcepcionSoftwareAction::make(),
                    RechazarExcepcionSoftwareAction::make(),
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
