<?php

namespace App\Filament\Resources\Licencias\Tables;

use App\Filament\Resources\Licencias\LicenciaResource;
use App\Models\Licencia;
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

class LicenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Licencia $record): string => LicenciaResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('nombre_licencia')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('nombre_licencia')
                    ->label('Nombre de la licencia')
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedKey)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('categoria')
                    ->label('Categoria')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('estado')
                    ->label('Activa')
                    ->boolean()
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
                    ->options([
                        'MICROSOFT' => 'MICROSOFT',
                        'ADOBE' => 'ADOBE',
                        'ANTIVIRUS' => 'ANTIVIRUS',
                        'VPN' => 'VPN',
                        'OTRO' => 'OTRO',
                    ]),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activa',
                        '0' => 'Inactiva',
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
                        ->successNotificationTitle('Licencia eliminada correctamente'),
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
                        ->successNotificationTitle('Licencias eliminadas correctamente'),
                ]),
            ]);
    }
}
