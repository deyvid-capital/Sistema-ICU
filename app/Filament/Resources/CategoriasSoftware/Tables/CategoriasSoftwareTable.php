<?php

namespace App\Filament\Resources\CategoriasSoftware\Tables;

use App\Filament\Resources\CategoriasSoftware\CategoriaSoftwareResource;
use App\Models\CategoriaSoftware;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriasSoftwareTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (CategoriaSoftware $record): string => CategoriaSoftwareResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('nombre')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->badge()
                    ->color(fn (CategoriaSoftware $record): string|array => $record->color())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('codigo')
                    ->label('Código')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('software_count')
                    ->label('Software')
                    ->counts('software')
                    ->numeric()
                    ->sortable(),
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
                        ->successNotificationTitle('Categoría eliminada correctamente'),
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
                        ->label('Eliminar seleccionadas')
                        ->successNotificationTitle('Categorías eliminadas correctamente'),
                ]),
            ]);
    }
}
