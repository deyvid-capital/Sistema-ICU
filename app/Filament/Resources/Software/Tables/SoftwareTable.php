<?php

namespace App\Filament\Resources\Software\Tables;

use App\Enums\CategoriasSoftware;
use App\Filament\Resources\Software\SoftwareResource;
use App\Models\Software;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SoftwareTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Software $record): string => SoftwareResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('nombre_software')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('categoria')
                    ->label('Categoria')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nombre_software')
                    ->label('Nombre del software')
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->sortable()
                    ->searchable(),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->sortable()
                    ->boolean(),
                TextColumn::make('fecha_autorizacion')
                    ->label('Fecha de autorizacion')
                    ->date('d/m/Y')
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
            ->filters([
                SelectFilter::make('categoria')
                    ->label('Categoria')
                    ->searchable()
                    ->options(CategoriasSoftware::getOptions()),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ]),
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
                        ->successNotificationTitle('Software eliminado correctamente'),
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
                        ->successNotificationTitle('Software eliminado correctamente'),
                ]),
            ]);
    }
}
