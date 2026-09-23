<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Actions\ActivityTimelineAction;
use App\Filament\Actions\GuiaImportAction;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\Seguridad\ProteccionSuperAdmin;
use App\Support\Sessions\UserSessions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UsersTable
{
    /**
     * Paleta rotativa para distinguir visualmente cada área.
     *
     * @var array<int, array<int, string>>
     */
    private const COLORES_AREA = [
        Color::Blue,
        Color::Emerald,
        Color::Amber,
        Color::Rose,
        Color::Violet,
        Color::Cyan,
        Color::Orange,
        Color::Teal,
        Color::Fuchsia,
        Color::Lime,
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['area', 'cargo', 'ultimoMovimiento']))

            ->columns([
                TextColumn::make('nombres')
                    ->label('Nombre')
                    ->description(fn (User $record): ?string => $record->apellidos ? $record->apellidos : null)
                    ->searchable(['name', 'carnet', 'apellidos'])
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser),

                TextColumn::make('area.area')
                    ->label('Cargo y Área')
                    ->description(
                        fn (User $record): ?string => $record->cargo?->cargo ? 'Cargo: '.$record->cargo->cargo : null,
                        position: 'above',
                    )
                    ->badge()
                    ->color(fn (User $record): string|array => self::colorDeArea($record->id_area))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->whereHas('area', fn (Builder $q) => $q->where('area', 'ilike', "%{$search}%"))
                                ->orWhereHas('cargo', fn (Builder $q) => $q->where('cargo', 'ilike', "%{$search}%"));
                        });
                    }),

                TextColumn::make('email')
                    ->label('Correo')
                    ->icon(Heroicon::Envelope)
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Correo copiado')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->icon(Heroicon::OutlinedPhone)
                    ->placeholder('Sin teléfono')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ultimoMovimiento.tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (User $record): string => $record->ultimoMovimiento?->tipo === 'Alta' ? 'success' : 'danger')
                    ->placeholder('-'),

                TextColumn::make('estado_acceso')
                    ->label('Acceso')
                    ->badge()
                    ->state(fn (User $record): string => match (true) {
                        $record->estaBloqueado() => 'Bloqueado',
                        ! $record->acceso_habilitado => 'Deshabilitado',
                        $record->acceso_expira_at !== null && ! $record->acceso_expira_at->isFuture() => 'Vencido',
                        default => 'Vigente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Vigente' => 'success',
                        'Vencido' => 'warning',
                        default => 'danger',
                    })
                    ->icon(fn (string $state): Heroicon => $state === 'Vigente' ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedLockClosed),

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
            ->groups([
                Group::make('area.area')
                    ->label('Área')
                    ->getTitleFromRecordUsing(fn (User $record): string => $record->area?->area ?? 'Sin área')
                    ->collapsible(),

                Group::make('cargo.cargo')
                    ->label('Cargo')
                    ->getTitleFromRecordUsing(fn (User $record): string => $record->cargo?->cargo ?? 'Sin cargo')
                    ->collapsible(),
            ])->collapsedGroupsByDefault(true)
            ->headerActions([
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->label('Lista usuarios')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('gray')
                    ->columnMapping(false)
                    ->modalHeading('Exportar lista de usuarios')
                    ->modalDescription('Se exportarán solo los usuarios con Alta vigente (sin Baja posterior) en formato Excel (.xlsx).'),
                GuiaImportAction::make()
                    ->importer(UserImporter::class)
                    ->maxRows(200)
                    ->label('Importar')
                    ->modalHeading('Importar usuarios'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Estado del registro'),

                SelectFilter::make('id_area')
                    ->label('Área')
                    ->relationship('area', 'area')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('id_cargo')
                    ->label('Cargo')
                    ->relationship('cargo', 'cargo')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('tipo_movimiento')
                    ->label('Tipo de movimiento')
                    ->options([
                        'Alta' => 'Alta',
                        'Baja' => 'Baja',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        $tipo = $data['value'] ?? null;

                        if (blank($tipo)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'ultimoMovimiento',
                            fn (Builder $movimientoQuery): Builder => $movimientoQuery->where('tipo', $tipo),
                        );
                    }),

                Filter::make('bloqueados')
                    ->label('Solo bloqueados')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('bloqueado_at'))
                    ->toggle(),

                Filter::make('acceso_deshabilitado')
                    ->label('Acceso deshabilitado')
                    ->query(fn (Builder $query): Builder => $query->where('acceso_habilitado', false))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    ActivityTimelineAction::make(),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (User $record): bool => ! $record->trashed()),
                    Action::make('seguridad')
                        ->label('Seguridad')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->color('info')
                        ->url(fn (User $record): string => UserResource::getUrl('seguridad', ['record' => $record]))
                        ->visible(fn (User $record): bool => ! $record->trashed()
                            && (bool) auth()->user()?->can('manageSecurity', User::class)),
                    Action::make('toggleAcceso')
                        ->label(fn (User $record): string => $record->acceso_habilitado ? 'Deshabilitar acceso' : 'Habilitar acceso')
                        ->icon(fn (User $record): Heroicon => $record->acceso_habilitado ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedLockOpen)
                        ->color(fn (User $record): string => $record->acceso_habilitado ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => ! $record->trashed()
                            && (bool) auth()->user()?->can('manageSecurity', User::class)
                            && ! ProteccionSuperAdmin::esPropio($record))
                        ->action(function (User $record): void {
                            if (ProteccionSuperAdmin::esPropio($record)) {
                                Notification::make()
                                    ->title('Acción no permitida')
                                    ->body('No puedes cambiar tu propio acceso desde aquí.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->forceFill(['acceso_habilitado' => ! $record->acceso_habilitado])->save();

                            if (! $record->acceso_habilitado) {
                                UserSessions::terminate($record);
                            }

                            Notification::make()->success()->title('Acceso actualizado')->send();
                        }),
                    Action::make('desbloquear')
                        ->label('Desbloquear')
                        ->icon(Heroicon::OutlinedLockOpen)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => $record->estaBloqueado()
                            && (bool) auth()->user()?->can('manageSecurity', User::class))
                        ->action(function (User $record): void {
                            $record->desbloquear();

                            Notification::make()->success()->title('Usuario desbloqueado')->send();
                        }),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (User $record): bool => ! $record->trashed()
                                    && $record->id !== auth()->user()->id),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (User $record): bool => $record->trashed()),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (User $record): bool => $record->trashed()),
                ])
                    ->label('')
                    ->icon(Heroicon::EllipsisVertical)
                    ->label('')
                    ->color('primary')
                    ->button()
                    ->tooltip('Acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('habilitarAcceso')
                        ->label('Habilitar acceso')
                        ->icon(Heroicon::OutlinedLockOpen)
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => (bool) auth()->user()?->can('manageSecurity', User::class))
                        ->action(function (Collection $records): void {
                            $records->each(fn (User $user): bool => $user->forceFill(['acceso_habilitado' => true])->save());

                            Notification::make()->success()->title('Acceso habilitado a los seleccionados')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deshabilitarAcceso')
                        ->label('Deshabilitar acceso')
                        ->icon(Heroicon::OutlinedLockClosed)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => (bool) auth()->user()?->can('manageSecurity', User::class))
                        ->action(function (Collection $records): void {
                            $records->each(function (User $user): void {
                                $user->forceFill(['acceso_habilitado' => false])->save();
                                UserSessions::terminate($user);
                            });

                            Notification::make()->success()->title('Acceso deshabilitado a los seleccionados')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('cerrarSesiones')
                        ->label('Cerrar sesiones')
                        ->icon(Heroicon::OutlinedArrowRightStartOnRectangle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => (bool) auth()->user()?->can('manageSecurity', User::class))
                        ->action(function (Collection $records): void {
                            $records->each(fn (User $user): int => UserSessions::terminate($user));

                            Notification::make()->success()->title('Sesiones cerradas para los seleccionados')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados'),
                ])
                    ->label('Acciones masivas'),
            ])
            ->recordUrl(fn (User $record): string => $record->trashed()
                ? UserResource::getUrl('view', ['record' => $record])
                : UserResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('name')
            ->striped()
            ->paginated([25, 50, 100])
            ->checkIfRecordIsSelectableUsing(fn (User $record): bool => $record->id !== auth()->id());
    }

    /**
     * Color estable asignado a un área, tomado de la paleta rotativa.
     *
     * @return string|array<int, string>
     */
    private static function colorDeArea(?int $idArea): string|array
    {
        if ($idArea === null) {
            return 'gray';
        }

        return self::COLORES_AREA[$idArea % count(self::COLORES_AREA)];
    }
}
