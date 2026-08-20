<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\RelationManagers\LicenciasUsuariosRelationManager;
use App\Filament\Resources\Users\RelationManagers\SoftwareExcepcionesRelationManager;
use App\Filament\Resources\Users\RelationManagers\SoftwareUsuariosRelationManager;
use Filament\Support\Icons\Heroicon;

class ManageUserSoftware extends ManageUserRelations
{
    protected static ?string $navigationLabel = 'Software y licencias';

    protected static ?int $navigationSort = 4;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected function relationManagers(): array
    {
        return [
            SoftwareUsuariosRelationManager::class,
            SoftwareExcepcionesRelationManager::class,
            LicenciasUsuariosRelationManager::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected static function countedRelationships(): array
    {
        return [
            'softwareUsuarios',
            'softwareExcepciones',
            'licenciasUsuarios',
        ];
    }
}
