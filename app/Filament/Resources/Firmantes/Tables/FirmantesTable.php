<?php

namespace App\Filament\Resources\Firmantes\Tables;

use App\Filament\Resources\EntregaEquipos\RelationManagers\FirmantesRelationManager as EntregaFirmantesRelationManager;
use App\Filament\Resources\NotaRecepcionEquipos\RelationManagers\FirmantesRelationManager as NotaRecepcionFirmantesRelationManager;
use App\Filament\Resources\NotaSalidaEquipos\RelationManagers\FirmantesRelationManager as NotaSalidaFirmantesRelationManager;
use App\Filament\Resources\RecepcionEquipos\RelationManagers\FirmantesRelationManager as RecepcionFirmantesRelationManager;
use App\Models\Firmante;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class FirmantesTable
{
    /**
     * @var list<class-string>
     */
    private const OWNER_RELATION_MANAGERS = [
        EntregaFirmantesRelationManager::class,
        RecepcionFirmantesRelationManager::class,
        NotaSalidaFirmantesRelationManager::class,
        NotaRecepcionFirmantesRelationManager::class,
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser),
                TextColumn::make('fecha')
                    ->label('Fecha de firma')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('documento_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => class_basename((string) $state))
                    ->searchable()
                    ->hiddenOn(self::OWNER_RELATION_MANAGERS),
                TextColumn::make('documento_id')
                    ->label('Documento')
                    ->numeric()
                    ->sortable()
                    ->hiddenOn(self::OWNER_RELATION_MANAGERS),
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
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (Firmante $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (Firmante $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Firmante eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (Firmante $record): bool => $record->trashed())
                        ->successNotificationTitle('Firmante eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (Firmante $record): bool => $record->trashed())
                        ->successNotificationTitle('Firmante restaurado correctamente'),
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
                        ->successNotificationTitle('Firmantes eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Firmantes eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Firmantes restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
