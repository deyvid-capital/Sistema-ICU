<?php

namespace App\Filament\Resources\Celulares;

use App\Filament\Clusters\Celulares\CelularesCluster;
use App\Filament\Resources\Celulares\Pages\CreateCelular;
use App\Filament\Resources\Celulares\Pages\EditCelular;
use App\Filament\Resources\Celulares\Pages\ListCelulars;
use App\Filament\Resources\Celulares\Pages\ViewCelular;
use App\Filament\Resources\Celulares\Schemas\CelularForm;
use App\Filament\Resources\Celulares\Schemas\CelularInfolist;
use App\Filament\Resources\Celulares\Tables\CelularsTable;
use App\Models\Celular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager;

class CelularResource extends Resource
{
    protected static ?string $model = Celular::class;

    protected static ?string $cluster = CelularesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Celulares';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'celular';

    protected static ?string $pluralModelLabel = 'celulares';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return CelularForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CelularInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CelularsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ActivitylogRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'marca', 'modelo', 'imei'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCelulars::route('/'),
            'create' => CreateCelular::route('/create'),
            'view' => ViewCelular::route('/{record}'),
            'edit' => EditCelular::route('/{record}/edit'),
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
