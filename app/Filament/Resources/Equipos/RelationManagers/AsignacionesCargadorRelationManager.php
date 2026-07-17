<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use App\Filament\Concerns\ConfirmsAsignacionTemporalCargador;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use App\Models\AsignacionCargador;
use App\Services\SincronizarAsignacionCargador;
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

class AsignacionesCargadorRelationManager extends RelationManager
{
    use ConfirmsAsignacionTemporalCargador;
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'asignacionesCargador';

    protected static ?string $title = 'Asignaciones de cargador';

    public function form(Schema $schema): Schema
    {
        return AsignacionCargadorResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $modoAsignacionEspecial = null;

        return AsignacionCargadorResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar cargador')
                    ->successNotificationTitle('Asignación registrada correctamente')
                    ->before(function (array $data, RelationManager $livewire) use (&$modoAsignacionEspecial): void {
                        $modoAsignacionEspecial = $data['modo_asignacion_especial'] ?? null;

                        app(SincronizarAsignacionCargador::class)->antesDeGuardar([
                            'serial_cargador' => (string) ($data['serial_cargador'] ?? ''),
                            'service_tag' => (string) $livewire->getOwnerRecord()->service_tag,
                        ]);
                    })
                    ->after(function (AsignacionCargador $record) use (&$modoAsignacionEspecial): void {
                        app(SincronizarAsignacionCargador::class)->despuesDeGuardar($record, $modoAsignacionEspecial);
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación actualizada correctamente')
                        ->before(function (array $data, RelationManager $livewire, AsignacionCargador $record) use (&$modoAsignacionEspecial): void {
                            $modoAsignacionEspecial = $data['modo_asignacion_especial'] ?? null;

                            app(SincronizarAsignacionCargador::class)->antesDeGuardar(
                                [
                                    'serial_cargador' => (string) ($data['serial_cargador'] ?? ''),
                                    'service_tag' => (string) $livewire->getOwnerRecord()->service_tag,
                                ],
                                ignoreAsignacionId: $record->getKey(),
                            );
                        })
                        ->after(function (AsignacionCargador $record) use (&$modoAsignacionEspecial): void {
                            app(SincronizarAsignacionCargador::class)->despuesDeGuardar($record->refresh(), $modoAsignacionEspecial);
                        }),
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
