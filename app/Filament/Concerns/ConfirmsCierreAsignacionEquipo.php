<?php

namespace App\Filament\Concerns;

use App\Enums\EstadoEquipo;
use App\Models\Equipo;
use App\Services\CambiarEstadoEquipo;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Modal de confirmación: el nuevo estado del equipo cierra la asignación vigente.
 */
trait ConfirmsCierreAsignacionEquipo
{
    public function confirmarCierreAsignacionEquipoAction(): Action
    {
        return Action::make('confirmarCierreAsignacionEquipo')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('warning')
            ->modalHeading('Terminar asignación actual')
            ->modalDescription(fn (array $arguments): string => $this->descripcionCierreAsignacionEquipo($arguments))
            ->modalSubmitActionLabel('Aceptar')
            ->modalCancelActionLabel('Rechazar')
            ->action(function (array $arguments): void {
                $this->data['estado'] = $arguments['estado'] ?? null;
                $this->data['confirmar_cierre_asignacion'] = true;
            });
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function descripcionCierreAsignacionEquipo(array $arguments): string
    {
        $equipo = $this->getRecord();
        $estado = EstadoEquipo::tryFrom((string) ($arguments['estado'] ?? ''));

        if (! $equipo instanceof Equipo || $estado === null) {
            return 'Al continuar se cerrará la asignación vigente de este equipo.';
        }

        $activa = app(CambiarEstadoEquipo::class)->asignacionActiva($equipo);

        if ($activa === null) {
            return "El equipo pasará a «{$estado->getLabel()}».";
        }

        $descripcion = CambiarEstadoEquipo::descripcionAsignacion($activa);

        return "El equipo pasará a «{$estado->getLabel()}» y se terminará la asignación actual de {$descripcion}, "
            .'con fecha de fin '.now()->format('d/m/Y').'.';
    }
}
