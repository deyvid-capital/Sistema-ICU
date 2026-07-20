<?php

namespace App\Filament\Resources\UsuarioExternos\Tables;

use App\Filament\Resources\UsuarioExternos\UsuarioExternoResource;
use App\Models\UsuarioExterno;
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

class UsuarioExternosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (UsuarioExterno $record): string => $record->trashed()
                ? UsuarioExternoResource::getUrl('view', ['record' => $record])
                : UsuarioExternoResource::getUrl('edit', ['record' => $record]))
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
                        ->visible(fn (UsuarioExterno $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (UsuarioExterno $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Usuario externo eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (UsuarioExterno $record): bool => $record->trashed())
                        ->successNotificationTitle('Usuario externo eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (UsuarioExterno $record): bool => $record->trashed())
                        ->successNotificationTitle('Usuario externo restaurado correctamente'),
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
                        ->successNotificationTitle('Usuarios externos eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Usuarios externos eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Usuarios externos restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
