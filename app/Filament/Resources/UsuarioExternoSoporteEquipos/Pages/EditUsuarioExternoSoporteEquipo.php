<?php

namespace App\Filament\Resources\UsuarioExternoSoporteEquipos\Pages;

use App\Filament\Resources\UsuarioExternoSoporteEquipos\UsuarioExternoSoporteEquipoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUsuarioExternoSoporteEquipo extends EditRecord
{
    protected static string $resource = UsuarioExternoSoporteEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
