<?php

namespace App\Filament\Resources\AsignacionCargadors\Pages;

use App\Enums\EstadoCargador;
use App\Filament\Concerns\ConfirmsAsignacionTemporalCargador;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAsignacionCargador extends EditRecord
{
    use ConfirmsAsignacionTemporalCargador;

    protected static string $resource = AsignacionCargadorResource::class;

    protected ?string $modoAsignacion = null;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos de la asignación de cargador se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación de cargador eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AsignacionCargador $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de cargador eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AsignacionCargador $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de cargador restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->modoAsignacion = is_string($data['modo_asignacion'] ?? null)
            ? $data['modo_asignacion']
            : AsignacionCargadorWorkflow::parseModo($data['modo_asignacion'] ?? null)?->value;

        unset($data['modo_asignacion']);

        return $data;
    }

    protected function beforeSave(): void
    {
        $serial = (string) ($this->data['serial_cargador'] ?? '');
        $destino = (string) ($this->data['service_tag'] ?? '');
        $modo = AsignacionCargadorWorkflow::parseModo($this->modoAsignacion);
        $ignoreId = $this->record->getKey();

        $cargador = Cargador::query()->find($serial);

        if (! $cargador) {
            Notification::make()
                ->danger()
                ->title('Cargador no encontrado')
                ->body('Seleccione un cargador válido.')
                ->send();

            $this->halt();
        }

        if (AsignacionCargadorWorkflow::cargadorEstaBloqueado($cargador)) {
            $estado = $cargador->estado instanceof EstadoCargador
                ? $cargador->estado->getLabel()
                : (string) $cargador->estado;

            Notification::make()
                ->danger()
                ->title('Cargador no disponible')
                ->body("No se puede asignar: el cargador está en estado «{$estado}».")
                ->persistent()
                ->send();

            $this->halt();
        }

        $activa = AsignacionCargadorWorkflow::asignacionActivaDelCargador($serial, $ignoreId);

        if ($activa !== null && $activa->service_tag !== $destino) {
            if ($modo === null) {
                Notification::make()
                    ->warning()
                    ->title('Confirmación requerida')
                    ->body('Este cargador ya está asignado a otro equipo. Confirme si el movimiento es temporal o una reasignación permanente.')
                    ->persistent()
                    ->send();

                $this->halt();
            }

            $equipoOrigen = $activa->equipo;

            if ($equipoOrigen) {
                $problemas = AsignacionCargadorWorkflow::problemasEquipoOrigenLibre($equipoOrigen);

                foreach ($problemas as $problema) {
                    Notification::make()
                        ->warning()
                        ->title($problema['titulo'])
                        ->body($problema['cuerpo'])
                        ->persistent()
                        ->send();
                }

                if ($problemas !== []) {
                    $this->halt();
                }
            }

            $nota = AsignacionCargadorWorkflow::cerrarAsignacionesPreviasYAnotar(
                $serial,
                $destino,
                $modo,
                $ignoreId,
            );

            $this->data['observaciones'] = AsignacionCargadorWorkflow::appendObservacion(
                $this->data['observaciones'] ?? null,
                $nota,
            );
        } else {
            AsignacionCargador::query()
                ->where('service_tag', $destino)
                ->whereNull('fecha_fin')
                ->whereKeyNot($ignoreId)
                ->where('serial_cargador', '!=', $serial)
                ->update(['fecha_fin' => now()->toDateString()]);
        }
    }

    protected function afterSave(): void
    {
        $modo = AsignacionCargadorWorkflow::parseModo($this->modoAsignacion);

        // Si solo se cerró esta asignación, el cargador vuelve a disponible.
        if (filled($this->record->fecha_fin) && $modo === null) {
            AsignacionCargadorWorkflow::actualizarEstadoCargador(
                $this->record->serial_cargador,
                null,
                cerrada: true,
            );

            return;
        }

        AsignacionCargadorWorkflow::actualizarEstadoCargador(
            $this->record->serial_cargador,
            $modo,
            cerrada: filled($this->record->fecha_fin),
        );
    }
}
