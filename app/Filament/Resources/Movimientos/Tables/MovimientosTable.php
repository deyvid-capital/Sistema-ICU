<?php

namespace App\Filament\Resources\Movimientos\Tables;

use App\Filament\Resources\Concerns\MovimientoFormularioPdfActions;
use App\Filament\Resources\Movimientos\MovimientoResource;
use App\Models\Movimiento;
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

class MovimientosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Movimiento $record): string => MovimientoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (Movimiento $record): string => $record->tipo === 'Alta' ? 'success' : 'danger')
                    ->searchable(),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('cargo.cargo')
                    ->label('Cargo')
                    ->badge()
                    ->color('gray')
                    ->searchable(['cargo']),
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
                SelectFilter::make('tipo')
                    ->label('Tipo de movimiento')
                    ->options([
                        'Alta' => 'Alta',
                        'Baja' => 'Baja',
                    ]),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])

            ->recordActions([
                ActionGroup::make([
                    ...MovimientoFormularioPdfActions::make(),
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (Movimiento $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->successNotificationTitle('Movimiento eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Movimiento eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->successNotificationTitle('Movimiento restaurado correctamente'),
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
                        ->successNotificationTitle('Movimientos eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Movimientos eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Movimientos restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
