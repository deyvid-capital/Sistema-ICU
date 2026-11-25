<?php

namespace App\Filament\Resources\UsuarioExternoSoporteEquipos\Tables;

use App\Filament\Resources\UsuarioExternoSoporteEquipos\UsuarioExternoSoporteEquipoResource;
use App\Models\UsuarioExternoSoporteEquipo;
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

class UsuarioExternoSoporteEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (UsuarioExternoSoporteEquipo $record): string => $record->trashed()
                ? UsuarioExternoSoporteEquipoResource::getUrl('view', ['record' => $record])
                : UsuarioExternoSoporteEquipoResource::getUrl('edit', ['record' => $record]))
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
                        ->visible(fn (UsuarioExternoSoporteEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (UsuarioExternoSoporteEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Usuario externo de soporte eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (UsuarioExternoSoporteEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Usuario externo de soporte eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (UsuarioExternoSoporteEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Usuario externo de soporte restaurado correctamente'),
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
                        ->successNotificationTitle('Usuarios externos de soporte eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Usuarios externos de soporte eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Usuarios externos de soporte restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
