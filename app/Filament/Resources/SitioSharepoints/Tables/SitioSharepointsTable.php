<?php

namespace App\Filament\Resources\SitioSharepoints\Tables;

use App\Filament\Resources\SitioSharepoints\SitioSharepointResource;
use App\Models\SitioSharepoint;
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

class SitioSharepointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (SitioSharepoint $record): string => SitioSharepointResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('nombre_sitio')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('nombre_sitio')
                    ->label('Nombre del sitio')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedGlobeAlt),
                TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (SitioSharepoint $record): ?string => $record->url),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('accesos_count')
                    ->label('Accesos')
                    ->counts('accesos')
                    ->badge()
                    ->color('info'),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                        ->successNotificationTitle('Sitio eliminado correctamente'),
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
                        ->successNotificationTitle('Sitios eliminados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
