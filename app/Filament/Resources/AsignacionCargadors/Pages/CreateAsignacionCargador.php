<?php

namespace App\Filament\Resources\AsignacionCargadors\Pages;

use App\Enums\EstadoCargador;
use App\Filament\Concerns\ConfirmsAsignacionTemporalCargador;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAsignacionCargador extends CreateRecord
{
    use ConfirmsAsignacionTemporalCargador;

    protected static string $resource = AsignacionCargadorResource::class;

    protected static bool $canCreateAnother = false;

    protected ?string $modoAsignacion = null;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación registrada')
            ->body('La asignación de cargador fue registrada correctamente.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->modoAsignacion = is_string($data['modo_asignacion'] ?? null)
            ? $data['modo_asignacion']
            : AsignacionCargadorWorkflow::parseModo($data['modo_asignacion'] ?? null)?->value;

        unset($data['modo_asignacion']);

        return $data;
    }

    protected function beforeCreate(): void
    {
        $serial = (string) ($this->data['serial_cargador'] ?? '');
        $destino = (string) ($this->data['service_tag'] ?? '');
        $modo = AsignacionCargadorWorkflow::parseModo($this->modoAsignacion);

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

        $activa = AsignacionCargadorWorkflow::asignacionActivaDelCargador($serial);

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
            );

            $this->data['observaciones'] = AsignacionCargadorWorkflow::appendObservacion(
                $this->data['observaciones'] ?? null,
                $nota,
            );
        } else {
            // Liberar otro cargador activo del equipo destino (si hubiera).
            AsignacionCargador::query()
                ->where('service_tag', $destino)
                ->whereNull('fecha_fin')
                ->where('serial_cargador', '!=', $serial)
                ->update(['fecha_fin' => now()->toDateString()]);
        }
    }

    protected function afterCreate(): void
    {
        AsignacionCargadorWorkflow::actualizarEstadoCargador(
            $this->record->serial_cargador,
            AsignacionCargadorWorkflow::parseModo($this->modoAsignacion),
            cerrada: filled($this->record->fecha_fin),
        );
    }
}
