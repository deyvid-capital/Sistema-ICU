<?php

namespace App\Filament\Resources\ReciboEquipos\Tables;

use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use App\Models\ReciboEquipo;
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

class ReciboEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (ReciboEquipo $record): string => $record->trashed()
                ? ReciboEquipoResource::getUrl('view', ['record' => $record])
                : ReciboEquipoResource::getUrl('edit', ['record' => $record]))
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
                    ->placeholder('—'),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('url')
                    ->label('URL')
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
                        ->visible(fn (ReciboEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (ReciboEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Recibo de equipo eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (ReciboEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Recibo de equipo eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (ReciboEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Recibo de equipo restaurado correctamente'),
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
                        ->successNotificationTitle('Recibos de equipo eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Recibos de equipo eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Recibos de equipo restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
