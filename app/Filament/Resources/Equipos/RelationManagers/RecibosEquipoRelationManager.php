<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use App\Filament\Resources\ReciboEquipos\Actions\ReciboEquipoDocumentActions;
use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use App\Models\ReciboEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RecibosEquipoRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'recibos';

    protected static ?string $title = 'Recibos';

    public function form(Schema $schema): Schema
    {
        return ReciboEquipoResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ReciboEquipoResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar recibo')
                    ->successNotificationTitle('Recibo registrado correctamente'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ReciboEquipoDocumentActions::viewFullScreen(),
                    ReciboEquipoDocumentActions::download(),
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (ReciboEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (ReciboEquipo $record): bool => ! $record->trashed())
                        ->successNotification(
                            fn (ReciboEquipo $record): Notification => ReciboEquipoResource::getDeletedNotification($record),
                        ),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (ReciboEquipo $record): bool => $record->trashed())
                        ->successNotification(
                            fn (ReciboEquipo $record): Notification => ReciboEquipoResource::getDeletedNotification($record, force: true),
                        ),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (ReciboEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Recibo de equipo restaurado correctamente'),
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
                        ->successNotificationTitle('Recibos de equipo eliminados correctamente. Los documentos se conservan por si deseas restaurarlos.'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Recibos eliminados permanentemente. Los documentos PDF asociados también se eliminaron si existían.'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Recibos de equipo restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
