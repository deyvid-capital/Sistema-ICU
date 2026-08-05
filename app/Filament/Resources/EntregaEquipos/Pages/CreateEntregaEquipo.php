<?php

namespace App\Filament\Resources\EntregaEquipos\Pages;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\DispatchesEquipoFormularioPdfAfterPersist;
use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateEntregaEquipo extends CreateRecord
{
    use DispatchesEquipoFormularioPdfAfterPersist;

    protected static string $resource = EntregaEquipoResource::class;

    protected static bool $canCreateAnother = false;

    protected function equipoFormularioPdfTipo(): EquipoFormularioPdfTipo
    {
        return EquipoFormularioPdfTipo::Entrega;
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

    protected function afterCreate(): void
    {
        $this->dispatchEquipoFormularioPdfIfRequested();
    }
}
