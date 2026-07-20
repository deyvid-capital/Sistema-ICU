<?php

namespace App\Filament\Resources\UsuarioExternos\Pages;

use App\Filament\Resources\UsuarioExternos\UsuarioExternoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUsuarioExterno extends EditRecord
{
    protected static string $resource = UsuarioExternoResource::class;

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
