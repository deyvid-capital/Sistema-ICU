<?php

namespace App\Filament\Resources\AsignacionCargadors\Tables;

use App\Filament\Resources\AsignacionCargadors\Actions\RegistrarCargadorCompradoAction;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Models\AsignacionCargador;
use App\Models\Equipo;
use App\Models\User;
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
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AsignacionCargadorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'cargador',
                'equipo.asignacionActiva.usuario',
            ]))
            ->recordUrl(fn (AsignacionCargador $record): string => $record->trashed()
                ? AsignacionCargadorResource::getUrl('view', ['record' => $record])
                : AsignacionCargadorResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha_inicio', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('cargador.serial')
                    ->label('Cargador y equipo')
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->icon(Heroicon::OutlinedBolt)
                    ->description(fn (AsignacionCargador $record): string => static::descripcionEquipo($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::buscar($query, $search))
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('usuario')
                    ->label('Usuario')
                    ->state(fn (AsignacionCargador $record): string => static::nombreUsuario($record))
                    ->icon(Heroicon::OutlinedUser)
                    ->weight(FontWeight::Medium)
                    ->placeholder('Sin usuario')
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::buscarUsuario($query, $search)),
                TextColumn::make('fecha_inicio')
                    ->label('Alta / Baja')
                    ->badge()
                    ->color(fn (AsignacionCargador $record): string => $record->fecha_fin ? 'gray' : 'success')
                    ->formatStateUsing(fn (AsignacionCargador $record): string => 'Alta: '.($record->fecha_inicio?->format('d/m/Y') ?? '—'))
                    ->description(fn (AsignacionCargador $record): string => 'Baja: '.($record->fecha_fin?->format('d/m/Y') ?? 'Activa'))
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
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('serial_cargador')
                    ->label('Cargador')
                    ->relationship('cargador', 'serial')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('service_tag')
                    ->label('Equipo')
                    ->options(fn (): array => Equipo::query()
                        ->orderBy('service_tag')
                        ->get()
                        ->mapWithKeys(fn (Equipo $equipo): array => [
                            $equipo->service_tag => trim("{$equipo->service_tag} — {$equipo->nombre_equipo}"),
                        ])
                        ->all())
                    ->searchable(),
                SelectFilter::make('usuario_id')
                    ->label('Usuario')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $usuarioId): Builder => $query->whereHas(
                            'equipo.asignacionActiva',
                            fn (Builder $asignacion): Builder => $asignacion->where('usuario_id', $usuarioId),
                        ),
                    )),
                SelectFilter::make('estado_asignacion')
                    ->label('Estado de la asignación')
                    ->options([
                        'actuales' => 'Asignaciones actuales (sin fecha de baja)',
                        'concluidas' => 'Asignaciones concluidas (con fecha de baja)',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'actuales' => $query->whereNull('fecha_fin'),
                        'concluidas' => $query->whereNotNull('fecha_fin'),
                        default => $query,
                    }),

                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    RegistrarCargadorCompradoAction::make(),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación de cargador eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (AsignacionCargador $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación de cargador eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (AsignacionCargador $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación de cargador restaurada correctamente'),
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
                        ->successNotificationTitle('Asignaciones de cargador eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Asignaciones de cargador eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Asignaciones de cargador restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }

    private static function descripcionEquipo(AsignacionCargador $record): string
    {
        $equipo = $record->equipo;

        if ($equipo === null) {
            return 'Equipo: —';
        }

        return 'Equipo: '.trim("{$equipo->service_tag} — {$equipo->nombre_equipo}");
    }

    private static function nombreUsuario(AsignacionCargador $record): string
    {
        $usuario = $record->equipo?->asignacionActiva?->usuario;

        if ($usuario === null) {
            return 'Sin usuario';
        }

        return $usuario->name ?: trim("{$usuario->nombres} {$usuario->apellidos}");
    }

    private static function buscar(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query->whereHas('cargador', fn (Builder $cargador): Builder => $cargador
                ->where('serial', 'ilike', "%{$search}%")
                ->orWhere('marca', 'ilike', "%{$search}%")
                ->orWhere('modelo', 'ilike', "%{$search}%"))
                ->orWhereHas('equipo', fn (Builder $equipo): Builder => $equipo
                    ->where('service_tag', 'ilike', "%{$search}%")
                    ->orWhere('nombre_equipo', 'ilike', "%{$search}%"))
                ->orWhere(fn (Builder $query): Builder => static::buscarUsuario($query, $search));
        });
    }

    private static function buscarUsuario(Builder $query, string $search): Builder
    {
        return $query->whereHas(
            'equipo.asignacionActiva.usuario',
            fn (Builder $usuario): Builder => $usuario
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('nombres', 'ilike', "%{$search}%")
                ->orWhere('apellidos', 'ilike', "%{$search}%"),
        );
    }
}
