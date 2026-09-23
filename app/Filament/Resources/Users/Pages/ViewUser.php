<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $navigationLabel = 'Ver';

    protected static ?int $navigationSort = 1;

    /**
     * Carga por adelantado todas las relaciones que muestra el infolist.
     */
    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load([
            'area',
            'cargo',
            'movimientos.cargo',
            'accesosInternet',
            'accesosVirtual.virtual',
            'accesosVirtual.recursoVpn',
            'fortitoken',
            'accesosDaza.fondo',
            'accesosSistemas.sistema',
            'accesosSharepoint.sitio',
            'unidadesCompartidas',
            'azureAd',
            'softwareUsuarios.software',
            'softwareExcepciones.software',
            'softwareExcepciones.aprobador',
            'softwareExcepciones.areaAprobadora',
            'licenciasUsuarios.licencia',
            'asignacionesEquipos.equipo',
            'asignacionesCelulares.celular',
            'firmantes',
        ]);
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar usuario')
                ->visible(fn (User $record): bool => ! $record->trashed()
                                    && $record->id !== auth()->user()->id),

        ];
    }
}
