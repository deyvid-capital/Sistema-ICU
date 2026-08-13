<?php

namespace App\Filament\Resources\SoftwareExcs;

use App\Filament\Clusters\Software\SoftwareCluster;
use App\Filament\Resources\SoftwareExcs\Pages\CreateSoftwareExc;
use App\Filament\Resources\SoftwareExcs\Pages\EditSoftwareExc;
use App\Filament\Resources\SoftwareExcs\Pages\ListSoftwareExcs;
use App\Filament\Resources\SoftwareExcs\Pages\ViewSoftwareExc;
use App\Filament\Resources\SoftwareExcs\Schemas\SoftwareExcForm;
use App\Filament\Resources\SoftwareExcs\Schemas\SoftwareExcInfolist;
use App\Filament\Resources\SoftwareExcs\Tables\SoftwareExcsTable;
use App\Models\SoftwareExc;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SoftwareExcResource extends Resource
{
    protected static ?string $model = SoftwareExc::class;

    protected static ?string $cluster = SoftwareCluster::class;

    protected static ?string $recordTitleAttribute = 'tipo_excepcion';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $navigationLabel = 'Excepciones de software';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'excepcion de software';

    protected static ?string $pluralModelLabel = 'excepciones de software';

    public static function form(Schema $schema): Schema
    {
        return SoftwareExcForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SoftwareExcInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoftwareExcsTable::configure($table);
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
        return ['tipo_excepcion', 'motivo', 'usuario.name', 'software.nombre_software'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSoftwareExcs::route('/'),
            'create' => CreateSoftwareExc::route('/create'),
            'view' => ViewSoftwareExc::route('/{record}'),
            'edit' => EditSoftwareExc::route('/{record}/edit'),
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
