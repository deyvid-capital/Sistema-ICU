<?php

namespace App\Filament\Resources\Fondos;

use App\Filament\Clusters\Recursos\RecursosCluster;
use App\Filament\Resources\Fondos\Pages\CreateFondo;
use App\Filament\Resources\Fondos\Pages\EditFondo;
use App\Filament\Resources\Fondos\Pages\ListFondos;
use App\Filament\Resources\Fondos\Pages\ViewFondo;
use App\Filament\Resources\Fondos\RelationManagers\AccesosDazaRelationManager;
use App\Filament\Resources\Fondos\Schemas\FondoForm;
use App\Filament\Resources\Fondos\Schemas\FondoInfolist;
use App\Filament\Resources\Fondos\Tables\FondosTable;
use App\Models\Fondo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FondoResource extends Resource
{
    protected static ?string $model = Fondo::class;

    protected static ?string $cluster = RecursosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Fondos DAZA';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'fondo';

    protected static ?string $pluralModelLabel = 'fondos';

    protected static ?string $recordTitleAttribute = 'nombre_completo';

    public static function form(Schema $schema): Schema
    {
        return FondoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FondoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FondosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AccesosDazaRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['codigo', 'nombre_completo', 'descripcion'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFondos::route('/'),
            'create' => CreateFondo::route('/create'),
            'view' => ViewFondo::route('/{record}'),
            'edit' => EditFondo::route('/{record}/edit'),
        ];
    }
}
