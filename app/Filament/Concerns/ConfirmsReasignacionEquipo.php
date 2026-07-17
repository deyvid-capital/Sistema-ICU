<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Modal de confirmación simple: el equipo ya está asignado a otro usuario.
 */
trait ConfirmsReasignacionEquipo
{
    public function confirmarReasignacionEquipoAction(): Action
    {
        return Action::make('confirmarReasignacionEquipo')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('warning')
            ->modalHeading('Equipo ya asignado')
            ->modalDescription('Este equipo ya está asignado a otro usuario. Si continúa, se cerrará esa asignación y se asignará al usuario seleccionado.')
            ->modalSubmitActionLabel('Confirmar reasignación')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (array $arguments): void {
                $this->data['service_tag'] = $arguments['service_tag'] ?? null;
                $this->data['confirmar_reasignacion'] = true;
            });
    }
}
