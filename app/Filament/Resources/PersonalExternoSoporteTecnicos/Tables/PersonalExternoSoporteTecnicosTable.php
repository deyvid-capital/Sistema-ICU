<?php

namespace App\Filament\Resources\PersonalExternoSoporteTecnicos\Tables;

use App\Filament\Resources\PersonalExternoSoporteTecnicos\PersonalExternoSoporteTecnicoResource;
use App\Models\PersonalExternoSoporteTecnico;
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

class PersonalExternoSoporteTecnicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (PersonalExternoSoporteTecnico $record): string => $record->trashed()
                ? PersonalExternoSoporteTecnicoResource::getUrl('view', ['record' => $record])
                : PersonalExternoSoporteTecnicoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('nombre')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser),
                TextColumn::make('carnet')
                    ->label('Carnet / CI')
                    ->searchable(),
                TextColumn::make('empresa')
                    ->label('Empresa')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('cargo')
                    ->label('Cargo')
                    ->searchable(),
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
                        ->visible(fn (PersonalExternoSoporteTecnico $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (PersonalExternoSoporteTecnico $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Soporte externo eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (PersonalExternoSoporteTecnico $record): bool => $record->trashed())
                        ->successNotificationTitle('Soporte externo eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (PersonalExternoSoporteTecnico $record): bool => $record->trashed())
                        ->successNotificationTitle('Soporte externo restaurado correctamente'),
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
                        ->successNotificationTitle('Soportes externos eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Soportes externos eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Soportes externos restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
