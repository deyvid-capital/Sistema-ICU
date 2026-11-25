<?php

namespace App\Filament\Resources\SoftwareCargos;

use App\Filament\Clusters\Software\SoftwareCluster;
use App\Filament\Resources\SoftwareCargos\Pages\CreateSoftwareCargo;
use App\Filament\Resources\SoftwareCargos\Pages\EditSoftwareCargo;
use App\Filament\Resources\SoftwareCargos\Pages\ListSoftwareCargos;
use App\Filament\Resources\SoftwareCargos\Pages\ViewSoftwareCargo;
use App\Filament\Resources\SoftwareCargos\Schemas\SoftwareCargoForm;
use App\Filament\Resources\SoftwareCargos\Schemas\SoftwareCargoInfolist;
use App\Filament\Resources\SoftwareCargos\Tables\SoftwareCargosTable;
use App\Models\SoftwareCargo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SoftwareCargoResource extends Resource
{
    protected static ?string $model = SoftwareCargo::class;

    protected static ?string $cluster = SoftwareCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Software por cargo';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'software por cargo';

    protected static ?string $pluralModelLabel = 'software por cargo';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SoftwareCargoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SoftwareCargoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoftwareCargosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['software.nombre_software', 'cargo.cargo'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSoftwareCargos::route('/'),
            'create' => CreateSoftwareCargo::route('/create'),
            'view' => ViewSoftwareCargo::route('/{record}'),
            'edit' => EditSoftwareCargo::route('/{record}/edit'),
        ];
    }
}
