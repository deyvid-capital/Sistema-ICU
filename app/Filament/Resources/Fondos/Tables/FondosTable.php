<?php

namespace App\Filament\Resources\Fondos\Tables;

use App\Filament\Resources\Fondos\FondoResource;
use App\Models\Fondo;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FondosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Fondo $record): string => FondoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('codigo')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedBanknotes),
                TextColumn::make('nombre_completo')
                    ->label('Nombre completo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('accesos_daza_count')
                    ->label('Accesos DAZA')
                    ->counts('accesosDaza')
                    ->badge()
                    ->color('info'),
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
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->successNotificationTitle('Fondo eliminado correctamente'),
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
                        ->successNotificationTitle('Fondos eliminados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
