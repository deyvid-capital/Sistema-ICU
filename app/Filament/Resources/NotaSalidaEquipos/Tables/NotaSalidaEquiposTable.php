<?php

namespace App\Filament\Resources\NotaSalidaEquipos\Tables;

use App\Filament\Resources\Equipos\RelationManagers\NotasSalidaRelationManager as EquipoNotasSalidaRelationManager;
use App\Filament\Resources\NotaSalidaEquipos\NotaSalidaEquipoResource;
use App\Filament\Resources\UsuarioExternos\RelationManagers\NotasSalidaRelationManager as UsuarioExternoNotasSalidaRelationManager;
use App\Models\NotaSalidaEquipo;
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

class NotaSalidaEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (NotaSalidaEquipo $record): string => $record->trashed()
                ? NotaSalidaEquipoResource::getUrl('view', ['record' => $record])
                : NotaSalidaEquipoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('equipo.service_tag')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->placeholder('—')
                    ->hiddenOn(EquipoNotasSalidaRelationManager::class),
                TextColumn::make('usuarioExterno.nombre')
                    ->label('Usuario externo')
                    ->searchable()
                    ->placeholder('—')
                    ->hiddenOn(UsuarioExternoNotasSalidaRelationManager::class),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable(),
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
                        ->visible(fn (NotaSalidaEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (NotaSalidaEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Nota de salida eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (NotaSalidaEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Nota de salida eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (NotaSalidaEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Nota de salida restaurada correctamente'),
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
                        ->successNotificationTitle('Notas de salida eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Notas de salida eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Notas de salida restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
