<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\RelationManagers\UnidadesCompartidasRelationManager;
use Filament\Support\Icons\Heroicon;

class ManageUserRecursos extends ManageUserRelations
{
    protected static ?string $navigationLabel = 'Recursos compartidos';

    protected static ?int $navigationSort = 5;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected function relationManagers(): array
    {
        return [
            UnidadesCompartidasRelationManager::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected static function countedRelationships(): array
    {
        return [
            'unidadesCompartidas',
        ];
    }
}
