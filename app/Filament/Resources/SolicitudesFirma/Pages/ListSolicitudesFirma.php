<?php

namespace App\Filament\Resources\SolicitudesFirma\Pages;

use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSolicitudesFirma extends ListRecords
{
    protected static string $resource = SolicitudFirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Preparar envío'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $usuario = $this->usuario();

        $tabs = [
            'pendientes' => Tab::make('Pendientes de mi firma')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'firmantes',
                    fn (Builder $query) => $query->where('usuario_id', $usuario->getKey()),
                )),
            'enviados' => Tab::make('Enviados por mí')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('creado_por', $usuario->getKey())),
        ];

        if ($usuario->can('View:SolicitudFirma')) {
            $tabs['todos'] = Tab::make('Todos');
        }

        return $tabs;
    }

    private function usuario(): User
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return $usuario;
    }
}
