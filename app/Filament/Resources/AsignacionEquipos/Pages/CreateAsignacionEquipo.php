<?php

namespace App\Filament\Resources\AsignacionEquipos\Pages;

use App\Enums\EquipoFormularioPdfTipo;
use App\Enums\EstadoEquipo;
use App\Filament\Concerns\ConfirmsReasignacionEquipo;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoWorkflow;
use App\Filament\Resources\Concerns\DispatchesEquipoFormularioPdfAfterPersist;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAsignacionEquipo extends CreateRecord
{
    use ConfirmsReasignacionEquipo;
    use DispatchesEquipoFormularioPdfAfterPersist;

    protected static string $resource = AsignacionEquipoResource::class;

    protected static bool $canCreateAnother = false;

    protected function equipoFormularioPdfTipo(): EquipoFormularioPdfTipo
    {
        return EquipoFormularioPdfTipo::Entrega;
    }

    protected function formularioPdfRecord(): ?Model
    {
        /** @var AsignacionEquipo|null $asignacion */
        $asignacion = $this->record;

        return $asignacion?->entrega;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->createAndGeneratePdfFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación registrada')
            ->body('La asignación de equipo fue registrada correctamente.');
    }

    protected function beforeCreate(): void
    {
        $serviceTag = (string) ($this->data['service_tag'] ?? '');
        $usuarioDestino = (string) ($this->data['usuario_id'] ?? '');
        $confirmado = (bool) ($this->data['confirmar_reasignacion'] ?? false);

        $equipo = Equipo::query()->find($serviceTag);

        if (! $equipo) {
            Notification::make()->danger()->title('Equipo no encontrado')
                ->body('Seleccione un equipo válido.')->send();
            $this->halt();
        }

        if (AsignacionEquipoWorkflow::equipoEstaBloqueado($equipo)) {
            $estado = $equipo->estado instanceof EstadoEquipo
                ? $equipo->estado->getLabel() : (string) $equipo->estado;

            Notification::make()->danger()->title('Equipo no disponible')
                ->body("No se puede asignar: el equipo está en estado «{$estado}».")
                ->persistent()->send();
            $this->halt();
        }

        $activa = AsignacionEquipoWorkflow::asignacionActivaDelEquipo($serviceTag);

        if ($activa !== null && $activa->usuario_id !== $usuarioDestino) {
            if (! $confirmado) {
                Notification::make()->warning()->title('Confirmación requerida')
                    ->body('Este equipo ya está asignado a otro usuario. Confirme para cerrar esa asignación y reasignarlo.')
                    ->persistent()->send();
                $this->halt();
            }

            $nota = AsignacionEquipoWorkflow::cerrarAsignacionesPreviasYAnotar($serviceTag, $usuarioDestino);

            $this->data['observaciones'] = AsignacionEquipoWorkflow::appendObservacion(
                $this->data['observaciones'] ?? null, $nota,
            );
        }
    }

    protected function afterCreate(): void
    {
        AsignacionEquipoWorkflow::actualizarEstadoEquipo(
            $this->record->service_tag,
            cerrada: filled($this->record->fecha_fin),
        );

        $this->record->load('entrega');

        $this->dispatchEquipoFormularioPdfIfRequested();
    }
}
