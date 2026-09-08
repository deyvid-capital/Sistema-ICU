<?php

namespace App\Filament\Concerns;

use App\Enums\EstadoCargador;
use App\Models\Cargador;
use App\Services\CambiarEstadoCargador;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Modal: el nuevo estado del cargador cierra la asignación vigente.
 */
trait ConfirmsCierreAsignacionCargador
{
    public function confirmarCierreAsignacionCargadorAction(): Action
    {
        return Action::make('confirmarCierreAsignacionCargador')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('warning')
            ->modalHeading('Terminar asignación del cargador')
            ->modalDescription(fn (array $arguments): string => $this->descripcionCierreAsignacionCargador($arguments))
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
    protected function descripcionCierreAsignacionCargador(array $arguments): string
    {
        $cargador = $this->getRecord();
        $estado = EstadoCargador::tryFrom((string) ($arguments['estado'] ?? ''));

        if (! $cargador instanceof Cargador || $estado === null) {
            return 'Al continuar se cerrará la asignación vigente de este cargador.';
        }

        $activa = app(CambiarEstadoCargador::class)->asignacionActiva($cargador);
        $equipo = $activa?->equipo?->service_tag ?? $activa?->service_tag ?? 'el equipo';

        return "El cargador pasará a «{$estado->getLabel()}» y se terminará la asignación del equipo {$equipo}, "
            .'con fecha de baja '.now()->format('d/m/Y').'.';
    }
}
