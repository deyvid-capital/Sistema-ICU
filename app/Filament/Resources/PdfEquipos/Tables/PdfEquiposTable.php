<?php

namespace App\Filament\Resources\PdfEquipos\Tables;

use App\Filament\Resources\EntregaEquipos\RelationManagers\PdfsRelationManager as EntregaPdfsRelationManager;
use App\Filament\Resources\PdfEquipos\Actions\PdfEquipoDocumentActions;
use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use App\Filament\Resources\RecepcionEquipos\RelationManagers\PdfsRelationManager as RecepcionPdfsRelationManager;
use App\Models\PdfEquipo;
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
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PdfEquiposTable
{
    /**
     * @var list<class-string>
     */
    private const OWNER_RELATION_MANAGERS = [
        EntregaPdfsRelationManager::class,
        RecepcionPdfsRelationManager::class,
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->weight('medium'),
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
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('url')
                    ->label('Archivo')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? basename($state) : '—')
                    ->placeholder('—'),
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
                    ...PdfEquipoDocumentActions::make(),
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (PdfEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (PdfEquipo $record): bool => ! $record->trashed())
                        ->successNotification(
                            fn (PdfEquipo $record): Notification => PdfEquipoResource::getDeletedNotification($record),
                        ),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (PdfEquipo $record): bool => $record->trashed())
                        ->successNotification(
                            fn (PdfEquipo $record): Notification => PdfEquipoResource::getDeletedNotification($record, force: true),
                        ),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (PdfEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('PDF restaurado correctamente'),
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
                        ->successNotificationTitle('PDFs eliminados. Los archivos se conservan por si deseas restaurarlos.'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('PDFs eliminados permanentemente. Los archivos también se eliminaron si existían.'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('PDFs restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
