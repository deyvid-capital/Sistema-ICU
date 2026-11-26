<?php

namespace App\Filament\Resources\UsuariosExternos\Pages;

use App\Filament\Resources\Concerns\UsuarioExternoFormularioPdfActions;
use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewUsuarioExterno extends ViewRecord
{
    protected static string $resource = UsuarioExternoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...UsuarioExternoFormularioPdfActions::make(),
                EditAction::make()
                    ->label('Editar usuario externo'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
