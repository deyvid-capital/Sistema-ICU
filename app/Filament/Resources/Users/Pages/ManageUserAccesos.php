<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\RelationManagers\AccesosDazaRelationManager;
use App\Filament\Resources\Users\RelationManagers\AccesosInternetRelationManager;
use App\Filament\Resources\Users\RelationManagers\AccesosSharepointRelationManager;
use App\Filament\Resources\Users\RelationManagers\AccesosSistemasRelationManager;
use Filament\Support\Icons\Heroicon;

class ManageUserAccesos extends ManageUserRelations
{
    protected static ?string $navigationLabel = 'Accesos';

    protected static ?int $navigationSort = 5;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected function relationManagers(): array
    {
        return [
            AccesosSharepointRelationManager::class,
            AccesosSistemasRelationManager::class,
            AccesosDazaRelationManager::class,
            AccesosInternetRelationManager::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected static function countedRelationships(): array
    {
        return [
            'accesosSharepoint',
            'accesosSistemas',
            'accesosDaza',
            'accesosInternet',
        ];
    }
}
