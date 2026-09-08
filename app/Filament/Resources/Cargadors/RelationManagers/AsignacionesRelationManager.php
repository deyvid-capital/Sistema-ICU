<?php

namespace App\Filament\Resources\Cargadors\RelationManagers;

use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use App\Models\AsignacionCargador;
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

class AsignacionesRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'asignaciones';

    protected static ?string $title = 'Asignaciones';

    public function form(Schema $schema): Schema
    {
        return AsignacionCargadorResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AsignacionCargadorResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar a equipo')
                    ->successNotificationTitle('Asignación registrada correctamente')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['serial_cargador'] = $this->getOwnerRecord()->getKey();

                        return $data;
                    })
                    ->after(fn (AsignacionCargador $record) => AsignacionCargadorWorkflow::actualizarEstadoCargador(
                        $record->serial_cargador,
                        cerrada: filled($record->fecha_fin),
                    )),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación actualizada correctamente')
                        ->after(fn (AsignacionCargador $record) => AsignacionCargadorWorkflow::actualizarEstadoCargador(
                            $record->serial_cargador,
                            cerrada: filled($record->fecha_fin),
                        )),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (AsignacionCargador $record): bool => $record->trashed())
                        ->successNotificationTitle('Asignación eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (AsignacionCargador $record): bool => $record->trashed())
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
                    DeleteBulkAction::make()->label('Eliminar seleccionados'),
                    ForceDeleteBulkAction::make()->label('Eliminar permanentemente'),
                    RestoreBulkAction::make()->label('Restaurar seleccionados'),
                ])->label('Acciones masivas'),
            ]);
    }
}
