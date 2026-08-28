<?php

namespace App\Filament\Resources\AsignacionEquipos\Tables;

use App\Filament\Actions\ActivityTimelineAction;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesUsuarioRelationManager;
use App\Filament\Resources\Users\RelationManagers\AsignacionesEquipoRelationManager;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use Carbon\Carbon;
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
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AsignacionEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['entrega.pdfs']))
            ->recordUrl(fn (AsignacionEquipo $record): string => $record->trashed()
                ? AsignacionEquipoResource::getUrl('view', ['record' => $record])
                : AsignacionEquipoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha_inicio', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser)
                    ->hiddenOn(AsignacionesEquipoRelationManager::class),
                TextColumn::make('equipo.service_tag')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->hiddenOn(AsignacionesUsuarioRelationManager::class),
                TextColumn::make('fecha_inicio')
                    ->label('Alta')
                    ->weight(FontWeight::Bold)
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_fin')
                    ->label('Baja')
                    ->badge()
                    ->date('d/m/Y')
                    ->placeholder('Activa')
                    ->color(fn ($state): string => $state ? 'gray' : 'success')
                    ->sortable(),
                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('service_tag')
                    ->label('Equipo')
                    ->options(fn (): array => Equipo::query()
                        ->orderBy('service_tag')
                        ->pluck('service_tag', 'service_tag')
                        ->all())
                    ->searchable(),
                SelectFilter::make('estado_asignacion')
                    ->label('Estado de la asignación')
                    ->options([
                        'actuales' => 'Asignaciones actuales (sin fecha de fin)',
                        'concluidas' => 'Asignaciones concluidas (con fecha de fin)',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'actuales' => $query->whereNull('fecha_fin'),
                        'concluidas' => $query->whereNotNull('fecha_fin'),
                        default => $query,
                    }),
                Filter::make('fecha_inicio')
                    ->label('Fecha de Alta')
                    ->schema([
                        DatePicker::make('inicio_desde')
                            ->label('Desde'),
                        DatePicker::make('inicio_hasta')
                            ->label('Hasta')
                            ->afterOrEqual('inicio_desde'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['inicio_desde'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('fecha_inicio', '>=', $date),
                        )
                        ->when(
                            $data['inicio_hasta'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('fecha_inicio', '<=', $date),
                        ))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['inicio_desde'] ?? null) {
                            $indicators[] = Indicator::make('Alta desde '.Carbon::parse($data['inicio_desde'])->format('d/m/Y'))
                                ->removeField('inicio_desde');
                        }

                        if ($data['inicio_hasta'] ?? null) {
                            $indicators[] = Indicator::make('Alta hasta '.Carbon::parse($data['inicio_hasta'])->format('d/m/Y'))
                                ->removeField('inicio_hasta');
                        }

                        return $indicators;
                    }),
                Filter::make('fecha_fin')
                    ->label('Fecha de Baja')
                    ->schema([
                        DatePicker::make('fin_desde')
                            ->label('Desde'),
                        DatePicker::make('fin_hasta')
                            ->label('Hasta')
                            ->afterOrEqual('fin_desde'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['fin_desde'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('fecha_fin', '>=', $date),
                        )
                        ->when(
                            $data['fin_hasta'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('fecha_fin', '<=', $date),
                        ))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['fin_desde'] ?? null) {
                            $indicators[] = Indicator::make('Baja desde '.Carbon::parse($data['fin_desde'])->format('d/m/Y'))
                                ->removeField('fin_desde');
                        }

                        if ($data['fin_hasta'] ?? null) {
                            $indicators[] = Indicator::make('Baja hasta '.Carbon::parse($data['fin_hasta'])->format('d/m/Y'))
                                ->removeField('fin_hasta');
                        }

                        return $indicators;
                    }),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ...EquipoFormularioPdfActions::makeForAsignacionEntrega(),
                    ViewAction::make()
                        ->label('Ver'),
                    ActivityTimelineAction::make(),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AsignacionEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (AsignacionEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación de equipo eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (AsignacionEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación de equipo eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (AsignacionEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación de equipo restaurada correctamente'),
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
                        ->successNotificationTitle('Asignaciones de equipo eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Asignaciones de equipo eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Asignaciones de equipo restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
