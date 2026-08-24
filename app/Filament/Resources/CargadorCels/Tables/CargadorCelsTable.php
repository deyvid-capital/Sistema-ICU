<?php

namespace App\Filament\Resources\CargadorCels\Tables;

use App\Filament\Actions\ActivityTimelineAction;
use App\Filament\Resources\CargadorCels\CargadorCelResource;
use App\Models\CargadorCel;
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

class CargadorCelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (CargadorCel $record): string => $record->trashed()
                ? CargadorCelResource::getUrl('view', ['record' => $record])
                : CargadorCelResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('serial')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('serial')
                    ->label('Serial')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedBolt),
                TextColumn::make('modelo')->label('Modelo')->searchable()->placeholder('—'),
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
                        ->visible(fn (CargadorCel $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (CargadorCel $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Cargador de celular eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (CargadorCel $record): bool => $record->trashed())
                        ->successNotificationTitle('Cargador de celular eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (CargadorCel $record): bool => $record->trashed())
                        ->successNotificationTitle('Cargador de celular restaurado correctamente'),
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
                        ->successNotificationTitle('Cargadores de celular eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Cargadores de celular eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Cargadores de celular restaurados correctamente'),
                ])->label('Acciones masivas'),
            ]);
    }
}
