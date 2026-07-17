<?php

namespace App\Filament\Resources\Cargadors\RelationManagers;

use App\Enums\EstadoCargador;
use App\Enums\ModoAsignacionCargador;
use App\Filament\Concerns\ConfirmsAsignacionTemporalCargador;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use Filament\Actions\Action;
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

class AsignacionesRelationManager extends RelationManager
{
    use ConfirmsAsignacionTemporalCargador;
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'asignaciones';

    protected static ?string $title = 'Asignaciones';

    protected ?string $notaMovimiento = null;

    protected ?ModoAsignacionCargador $modoMovimiento = null;

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
                        unset($data['modo_asignacion']);

                        return $data;
                    })
                    ->before(function (array $data, CreateAction $action): void {
                        $this->prepararMovimientoCargador(
                            action: $action,
                            serial: (string) $this->getOwnerRecord()->getKey(),
                            destino: (string) ($data['service_tag'] ?? ''),
                            modoRaw: $this->data['modo_asignacion'] ?? ($data['modo_asignacion'] ?? null),
                        );
                    })
                    ->after(function (AsignacionCargador $record): void {
                        $this->aplicarNotaYEstado($record);
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Asignación actualizada correctamente')
                        ->mutateFormDataUsing(function (array $data): array {
                            unset($data['modo_asignacion']);

                            return $data;
                        })
                        ->before(function (array $data, EditAction $action, AsignacionCargador $record): void {
                            $this->prepararMovimientoCargador(
                                action: $action,
                                serial: (string) ($data['serial_cargador'] ?? $record->serial_cargador),
                                destino: (string) ($data['service_tag'] ?? $record->service_tag),
                                modoRaw: $this->data['modo_asignacion'] ?? ($data['modo_asignacion'] ?? null),
                                ignoreId: $record->getKey(),
                            );
                        })
                        ->after(function (AsignacionCargador $record): void {
                            $this->aplicarNotaYEstado($record);
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
                    DeleteBulkAction::make()->label('Eliminar seleccionados'),
                    ForceDeleteBulkAction::make()->label('Eliminar permanentemente'),
                    RestoreBulkAction::make()->label('Restaurar seleccionados'),
                ])->label('Acciones masivas'),
            ]);
    }

    protected function prepararMovimientoCargador(
        Action $action,
        string $serial,
        string $destino,
        mixed $modoRaw,
        ?int $ignoreId = null,
    ): void {
        $this->notaMovimiento = null;
        $this->modoMovimiento = AsignacionCargadorWorkflow::parseModo($modoRaw);
        $cargador = Cargador::query()->find($serial);

        if (! $cargador || AsignacionCargadorWorkflow::cargadorEstaBloqueado($cargador)) {
            $estado = $cargador?->estado instanceof EstadoCargador
                ? $cargador->estado->getLabel()
                : (string) ($cargador?->estado ?? 'desconocido');

            Notification::make()
                ->danger()
                ->title('Cargador no disponible')
                ->body("No se puede asignar: estado «{$estado}».")
                ->persistent()
                ->send();

            $action->halt();
        }

        $activa = AsignacionCargadorWorkflow::asignacionActivaDelCargador($serial, $ignoreId);

        if ($activa !== null && $activa->service_tag !== $destino) {
            if ($this->modoMovimiento === null) {
                Notification::make()
                    ->warning()
                    ->title('Confirmación requerida')
                    ->body('Confirme si el movimiento es temporal o una reasignación permanente.')
                    ->persistent()
                    ->send();

                $action->halt();
            }

            if ($activa->equipo) {
                $problemas = AsignacionCargadorWorkflow::problemasEquipoOrigenLibre($activa->equipo);

                foreach ($problemas as $problema) {
                    Notification::make()
                        ->warning()
                        ->title($problema['titulo'])
                        ->body($problema['cuerpo'])
                        ->persistent()
                        ->send();
                }

                if ($problemas !== []) {
                    $action->halt();
                }
            }

            $this->notaMovimiento = AsignacionCargadorWorkflow::cerrarAsignacionesPreviasYAnotar(
                $serial,
                $destino,
                $this->modoMovimiento,
                $ignoreId,
            );
        } else {
            AsignacionCargador::query()
                ->where('service_tag', $destino)
                ->whereNull('fecha_fin')
                ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
                ->where('serial_cargador', '!=', $serial)
                ->update(['fecha_fin' => now()->toDateString()]);
        }
    }

    protected function aplicarNotaYEstado(AsignacionCargador $record): void
    {
        if (filled($this->notaMovimiento)) {
            $record->update([
                'observaciones' => AsignacionCargadorWorkflow::appendObservacion(
                    $record->observaciones,
                    $this->notaMovimiento,
                ),
            ]);
        }

        AsignacionCargadorWorkflow::actualizarEstadoCargador(
            $record->serial_cargador,
            $this->modoMovimiento,
            cerrada: filled($record->fecha_fin),
        );

        $this->notaMovimiento = null;
        $this->modoMovimiento = null;
    }
}
