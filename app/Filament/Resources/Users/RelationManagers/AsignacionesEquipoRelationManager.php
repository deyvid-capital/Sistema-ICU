<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Concerns\ConfirmsReasignacionEquipo;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use App\Models\AsignacionEquipo;
use App\Services\SincronizarAsignacionEquipo;
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
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AsignacionesEquipoRelationManager extends RelationManager
{
    use ConfirmsReasignacionEquipo;
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'asignacionesEquipos';

    protected static ?string $title = 'Equipos asignados';

    public function form(Schema $schema): Schema
    {
        return AsignacionEquipoResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AsignacionEquipoResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar equipo')
                    ->successNotificationTitle('Asignación registrada correctamente')
                    ->after(fn (AsignacionEquipo $record) => app(SincronizarAsignacionEquipo::class)
                        ->sincronizarEstadoEquipo($record)),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AsignacionEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación actualizada correctamente')
                        ->after(fn (AsignacionEquipo $record) => app(SincronizarAsignacionEquipo::class)
                            ->sincronizarEstadoEquipo($record)),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (AsignacionEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (AsignacionEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (AsignacionEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación restaurada correctamente'),
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
                        ->label('Eliminar seleccionados'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
