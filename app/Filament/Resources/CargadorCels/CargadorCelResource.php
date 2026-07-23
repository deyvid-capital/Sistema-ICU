<?php

namespace App\Filament\Resources\CargadorCels;

use App\Filament\Resources\CargadorCels\Pages\CreateCargadorCel;
use App\Filament\Resources\CargadorCels\Pages\EditCargadorCel;
use App\Filament\Resources\CargadorCels\Pages\ListCargadorCels;
use App\Filament\Resources\CargadorCels\Pages\ViewCargadorCel;
use App\Filament\Resources\CargadorCels\Schemas\CargadorCelForm;
use App\Filament\Resources\CargadorCels\Schemas\CargadorCelInfolist;
use App\Filament\Resources\CargadorCels\Tables\CargadorCelsTable;
use App\Models\CargadorCel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CargadorCelResource extends Resource
{
    protected static ?string $model = CargadorCel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Celulares';

    protected static ?string $navigationLabel = 'Cargadores de celular';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'cargador de celular';

    protected static ?string $pluralModelLabel = 'cargadores de celular';

    protected static ?string $recordTitleAttribute = 'serial';

    public static function form(Schema $schema): Schema
    {
        return CargadorCelForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CargadorCelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CargadorCelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['serial', 'modelo'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCargadorCels::route('/'),
            'create' => CreateCargadorCel::route('/create'),
            'view' => ViewCargadorCel::route('/{record}'),
            'edit' => EditCargadorCel::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && ! $record->trashed();
    }
}
