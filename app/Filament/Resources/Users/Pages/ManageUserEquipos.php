<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\RelationManagers\AsignacionesEquipoRelationManager;
use Filament\Support\Icons\Heroicon;

class ManageUserEquipos extends ManageUserRelations
{
    protected static ?string $navigationLabel = 'Equipos';

    protected static ?int $navigationSort = 6;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected function relationManagers(): array
    {
        return [
            AsignacionesEquipoRelationManager::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected static function countedRelationships(): array
    {
        return [
            'asignacionesEquipos',
        ];
    }
}
