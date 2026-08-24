<?php

namespace App\Filament\Resources\Celulares\Tables;

use App\Filament\Actions\ActivityTimelineAction;
use App\Filament\Resources\Celulares\CelularResource;
use App\Models\Celular;
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

class CelularsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Celular $record): string => $record->trashed()
                ? CelularResource::getUrl('view', ['record' => $record])
                : CelularResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('id')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile),
                TextColumn::make('marca')->label('Marca')->searchable()->placeholder('—'),
                TextColumn::make('modelo')->label('Modelo')->searchable()->placeholder('—'),
                TextColumn::make('imei')->label('IMEI')->searchable()->placeholder('—'),
                TextColumn::make('fecha_compra')
                    ->label('Fecha de compra')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('precio')
                    ->label('Precio')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('ram')
                    ->label('RAM')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('disco')
                    ->label('Disco')
                    ->toggleable(isToggledHiddenByDefault: true)
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
                TrashedFilter::make()->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
                    ActivityTimelineAction::make(),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (Celular $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (Celular $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Celular eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (Celular $record): bool => $record->trashed())
                        ->successNotificationTitle('Celular eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (Celular $record): bool => $record->trashed())
                        ->successNotificationTitle('Celular restaurado correctamente'),
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
                        ->successNotificationTitle('Celulares eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Celulares eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Celulares restaurados correctamente'),
                ])->label('Acciones masivas'),
            ]);
    }
}
