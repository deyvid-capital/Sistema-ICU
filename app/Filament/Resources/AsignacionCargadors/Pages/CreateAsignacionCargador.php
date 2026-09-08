<?php

namespace App\Filament\Resources\AsignacionCargadors\Pages;

use App\Enums\EstadoCargador;
use App\Filament\Resources\AsignacionCargadors\Actions\UsarCargadorDeOtroEquipoAction;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Models\Cargador;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAsignacionCargador extends CreateRecord
{
    protected static string $resource = AsignacionCargadorResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            UsarCargadorDeOtroEquipoAction::make(),
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación registrada')
            ->body('La asignación de cargador fue registrada correctamente.');
    }

    protected function beforeCreate(): void
    {
        $serial = (string) ($this->data['serial_cargador'] ?? '');

        $cargador = Cargador::query()->find($serial);

        if (! $cargador) {
            Notification::make()->danger()->title('Cargador no encontrado')
                ->body('Seleccione un cargador válido.')->send();
            $this->halt();
        }

        $estado = $cargador->estado instanceof EstadoCargador
            ? $cargador->estado
            : EstadoCargador::tryFrom((string) $cargador->estado);

        if ($estado === null || ! $estado->isLibreParaAsignacionDirecta()) {
            $estadoLabel = $estado?->getLabel() ?? (string) $cargador->estado;

            Notification::make()->danger()->title('Cargador no disponible')
                ->body("Solo se pueden asignar cargadores en estado «Disponible». Estado actual: «{$estadoLabel}».")
                ->persistent()->send();
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        AsignacionCargadorWorkflow::actualizarEstadoCargador(
            $this->record->serial_cargador,
            cerrada: filled($this->record->fecha_fin),
        );
    }
}
