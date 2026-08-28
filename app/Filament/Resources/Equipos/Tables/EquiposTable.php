<?php

namespace App\Filament\Resources\Equipos\Tables;

use App\Enums\EstadoEquipo;
use App\Filament\Actions\ActivityTimelineAction;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Equipo $record): string => $record->trashed()
                ? EquipoResource::getUrl('view', ['record' => $record])
                : EquipoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('service_tag')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('service_tag')
                    ->label('Service Tag')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedComputerDesktop),
                TextColumn::make('nombre_equipo')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('modelo.nombre')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoEquipo|string|null $state): string => $state instanceof EstadoEquipo
                        ? $state->getLabel()
                        : (string) ($state ?? '—'))
                    ->color(fn (EstadoEquipo|string|null $state): string => $state instanceof EstadoEquipo
                        ? (string) $state->getColor()
                        : match ($state) {
                            'asignado' => 'success',
                            'sin asignar' => 'warning',
                            'en mantenimiento' => 'info',
                            'resguardo' => 'gray',
                            'baja' => 'danger',
                            default => 'gray',
                        }),
                TextColumn::make('codigo_activo')
                    ->label('Código activo')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                IconColumn::make('etiquetado')
                    ->label('Etiquetado')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('litigation_hold')
                    ->label('Litigation Hold')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('bitlocker')
                    ->label('BitLocker')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoEquipo::class)
                    ->native(false),
                SelectFilter::make('modelo_id')
                    ->label('Modelo')
                    ->relationship('modelo', 'nombre')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
                    ActivityTimelineAction::make(),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (Equipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (Equipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Equipo eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (Equipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Equipo eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (Equipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Equipo restaurado correctamente'),
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
                        ->successNotificationTitle('Equipos eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Equipos eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Equipos restaurados correctamente'),
                ])->label('Acciones masivas'),
            ]);
    }
}
