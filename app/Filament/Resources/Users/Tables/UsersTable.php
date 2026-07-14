<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['area', 'cargo', 'ultimoMovimiento']))

            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->description(fn (User $record): ?string => $record->carnet ? "Carnet: {$record->carnet}" : null)
                    ->searchable(['name', 'carnet'])
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser),

                TextColumn::make('area.area')
                    ->label('Cargo y Área')
                    ->description(
                        fn (User $record): ?string => $record->cargo?->cargo ? 'Cargo: '.$record->cargo->cargo : null,
                        position: 'above',
                    )
                    ->color('primary')
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
                    ->placeholder('Sin movimiento'),

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
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (User $record): bool => ! $record->trashed()),
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
                    ->icon(Heroicon::Bars4)
                    ->label('Acciones')
                    ->color('info')
                    ->button()
                    ->tooltip('Acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
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
            ->paginated([10, 25, 50])
            ->checkIfRecordIsSelectableUsing(fn (User $record): bool => $record->id !== auth()->id());
    }
}
