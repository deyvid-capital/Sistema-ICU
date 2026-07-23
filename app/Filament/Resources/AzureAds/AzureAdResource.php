<?php

namespace App\Filament\Resources\AzureAds;

use App\Filament\Resources\AzureAds\Pages\CreateAzureAd;
use App\Filament\Resources\AzureAds\Pages\EditAzureAd;
use App\Filament\Resources\AzureAds\Pages\ListAzureAds;
use App\Filament\Resources\AzureAds\Pages\ViewAzureAd;
use App\Filament\Resources\AzureAds\Schemas\AzureAdForm;
use App\Filament\Resources\AzureAds\Schemas\AzureAdInfolist;
use App\Filament\Resources\AzureAds\Tables\AzureAdsTable;
use App\Models\AzureAd;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AzureAdResource extends Resource
{
    protected static ?string $model = AzureAd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static string|UnitEnum|null $navigationGroup = 'Laptop y Computadoras';

    protected static ?string $navigationLabel = 'Azure AD';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'cuenta Azure AD';

    protected static ?string $pluralModelLabel = 'cuentas Azure AD';

    protected static ?string $recordTitleAttribute = 'iniciales';

    public static function form(Schema $schema): Schema
    {
        return AzureAdForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AzureAdInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AzureAdsTable::configure($table);
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
        return ['iniciales', 'estado', 'usuario.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAzureAds::route('/'),
            'create' => CreateAzureAd::route('/create'),
            'view' => ViewAzureAd::route('/{record}'),
            'edit' => EditAzureAd::route('/{record}/edit'),
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
