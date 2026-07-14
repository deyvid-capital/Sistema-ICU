<?php

namespace App\Filament\Resources\AzureAds\Tables;

use App\Filament\Resources\AzureAds\AzureAdResource;
use App\Filament\Resources\Users\RelationManagers\AzureAdRelationManager;
use App\Models\AzureAd;
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
use Illuminate\Database\Eloquent\Builder;

class AzureAdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (AzureAd $record): string => $record->trashed()
                ? AzureAdResource::getUrl('view', ['record' => $record])
                : AzureAdResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser)
                    ->hiddenOn(AzureAdRelationManager::class),
                TextColumn::make('iniciales')
                    ->label('Iniciales')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                TextColumn::make('sincronizacion')
                    ->label('Sincronización')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Nube' => 'success',
                        'Local / Nube' => 'warning',
                        'Local / No sincronizado' => 'danger',
                        'Error' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                IconColumn::make('mfa')
                    ->label('MFA')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('estado')
                    ->label('Activo')
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
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('sincronizacion')
                    ->label('Sincronización')
                    ->options([
                        'Nube' => 'Nube',
                        'Local /Nube' => 'Local / Nube',
                        'Local / No sincronizado' => 'Local / No sincronizado',
                    ])
                    ->native(false),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        $estado = $data['value'] ?? null;

                        if ($estado === null || $estado === '') {
                            return $query;
                        }

                        return $query->where('estado', (bool) (int) $estado);
                    }),
                SelectFilter::make('mfa')
                    ->label('MFA')
                    ->options([
                        '1' => 'Habilitado',
                        '0' => 'Deshabilitado',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        $mfa = $data['value'] ?? null;

                        if ($mfa === null || $mfa === '') {
                            return $query;
                        }

                        return $query->where('mfa', (bool) (int) $mfa);
                    }),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AzureAd $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (AzureAd $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Cuenta Azure AD eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (AzureAd $record): bool => $record->trashed())
                        ->successNotificationTitle('Cuenta Azure AD eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (AzureAd $record): bool => $record->trashed())
                        ->successNotificationTitle('Cuenta Azure AD restaurada correctamente'),
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
                        ->successNotificationTitle('Cuentas Azure AD eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Cuentas Azure AD eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Cuentas Azure AD restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
