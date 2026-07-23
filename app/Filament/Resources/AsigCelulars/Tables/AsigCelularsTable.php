<?php

namespace App\Filament\Resources\AsigCelulars\Tables;

use App\Filament\Resources\AsigCelulars\AsigCelularResource;
use App\Models\AsigCelular;
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

class AsigCelularsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (AsigCelular $record): string => $record->trashed()
                ? AsigCelularResource::getUrl('view', ['record' => $record])
                : AsigCelularResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha_ini', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('celular.id')
                    ->label('Celular')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->placeholder('—'),
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('fecha_ini')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
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
                        ->visible(fn (AsigCelular $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (AsigCelular $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación de celular eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (AsigCelular $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación de celular eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (AsigCelular $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación de celular restaurada correctamente'),
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
                        ->successNotificationTitle('Asignaciones de celular eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Asignaciones de celular eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Asignaciones de celular restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
