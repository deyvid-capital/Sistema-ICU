<?php

namespace App\Filament\Concerns;

use App\Enums\ModoAsignacionCargador;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Support\Icons\Heroicon;

/**
 * Modal simple: Temporal o Reasignación permanente.
 */
trait ConfirmsAsignacionTemporalCargador
{
    public function confirmarMovimientoCargadorAction(): Action
    {
        return Action::make('confirmarMovimientoCargador')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('warning')
            ->modalHeading('Cargador ya asignado')
            ->modalDescription('Este cargador ya está asignado a otro equipo. Elija si el movimiento es temporal o una reasignación permanente.')
            ->modalSubmitActionLabel('Confirmar')
            ->modalCancelActionLabel('Cancelar')
            ->schema([
                Radio::make('modo')
                    ->label('Tipo de movimiento')
                    ->options(ModoAsignacionCargador::class)
                    ->default(ModoAsignacionCargador::Temporal->value)
                    ->required()
                    ->helperText('Temporal: contingencia. Reasignación: queda de forma permanente en el nuevo equipo.'),
            ])
            ->action(function (array $arguments, array $data): void {
                $modo = $data['modo'] ?? ModoAsignacionCargador::Temporal->value;

                if ($modo instanceof ModoAsignacionCargador) {
                    $modo = $modo->value;
                }

                $this->data['serial_cargador'] = $arguments['serial'] ?? null;
                $this->data['modo_asignacion'] = $modo;
            });
    }
}
