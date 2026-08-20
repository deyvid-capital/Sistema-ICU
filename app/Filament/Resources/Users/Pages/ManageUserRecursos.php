<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\RelationManagers\AsignacionesEquipoRelationManager;
use App\Filament\Resources\Users\RelationManagers\AzureAdRelationManager;
use App\Filament\Resources\Users\RelationManagers\UnidadesCompartidasRelationManager;
use Filament\Support\Icons\Heroicon;

class ManageUserRecursos extends ManageUserRelations
{
    protected static ?string $navigationLabel = 'Carpetas, AD y equipos';

    protected static ?int $navigationSort = 6;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected function relationManagers(): array
    {
        return [
            UnidadesCompartidasRelationManager::class,
            AzureAdRelationManager::class,
            AsignacionesEquipoRelationManager::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected static function countedRelationships(): array
    {
        return [
            'unidadesCompartidas',
            'azureAd',
            'asignacionesEquipos',
        ];
    }
}
