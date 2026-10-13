<?php

namespace App\Filament\Resources\Areas\Tables;

use App\Filament\Actions\GuiaImportAction;
use App\Filament\Imports\AreaImporter;
use App\Filament\Resources\Areas\AreaResource;
use App\Models\Area;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('area')
            ->recordUrl(fn (Area $record): string => AreaResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('area')
            ->striped()
            ->paginated([10, 25, 50])
            ->headerActions([
                GuiaImportAction::make()
                    ->importer(AreaImporter::class)
                    ->maxRows(200)
                    ->label('Importar')
                    ->modalHeading('Importar áreas'),
            ])
            ->columns([
                TextColumn::make('area')
                    ->label('Área')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedBuildingOffice2),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->placeholder('Sin descripción')
                    ->toggleable(),
                TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                TextColumn::make('cargos_count')
                    ->label('Cargos')
                    ->counts('cargos')
                    ->badge()
                    ->color('success'),
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
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->successNotificationTitle('Área actualizada correctamente'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->successNotificationTitle('Área eliminada correctamente'),
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
                        ->successNotificationTitle('Áreas eliminadas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
