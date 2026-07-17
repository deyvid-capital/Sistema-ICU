<?php

namespace App\Filament\Resources\Cargadors\Tables;

use App\Enums\EstadoCargador;
use App\Filament\Resources\Cargadors\CargadorResource;
use App\Models\Cargador;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CargadorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Cargador $record): string => $record->trashed()
                ? CargadorResource::getUrl('view', ['record' => $record])
                : CargadorResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('serial')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('serial')
                    ->label('Serial')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedBolt),
                TextColumn::make('marca')->label('Marca')->searchable()->placeholder('—'),
                TextColumn::make('modelo')->label('Modelo')->searchable()->placeholder('—'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoCargador|string|null $state): string => $state instanceof EstadoCargador
                        ? $state->getLabel()
                        : (string) ($state ?? '—'))
                    ->color(fn (EstadoCargador|string|null $state): string => $state instanceof EstadoCargador
                        ? (string) $state->getColor()
                        : match ($state) {
                            'disponible' => 'success',
                            'asignado' => 'info',
                            'temporal' => 'warning',
                            'reasignacion' => 'primary',
                            'danado' => 'danger',
                            'baja' => 'gray',
                            default => 'gray',
                        })
                    ->placeholder('—'),
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
                    ->options(EstadoCargador::class)
                    ->native(false),
                TrashedFilter::make()->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (Cargador $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (Cargador $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Cargador eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (Cargador $record): bool => $record->trashed())
                        ->successNotificationTitle('Cargador eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (Cargador $record): bool => $record->trashed())
                        ->successNotificationTitle('Cargador restaurado correctamente'),
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
                        ->successNotificationTitle('Cargadores eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Cargadores eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Cargadores restaurados correctamente'),
                ])->label('Acciones masivas'),
            ]);
    }
}
