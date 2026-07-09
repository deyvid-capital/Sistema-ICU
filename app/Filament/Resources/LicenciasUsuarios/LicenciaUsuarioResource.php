<?php

namespace App\Filament\Resources\LicenciasUsuarios;

use App\Filament\Resources\LicenciasUsuarios\Pages\CreateLicenciaUsuario;
use App\Filament\Resources\LicenciasUsuarios\Pages\EditLicenciaUsuario;
use App\Filament\Resources\LicenciasUsuarios\Pages\ListLicenciasUsuarios;
use App\Filament\Resources\LicenciasUsuarios\Pages\ViewLicenciaUsuario;
use App\Filament\Resources\LicenciasUsuarios\Schemas\LicenciaUsuarioForm;
use App\Filament\Resources\LicenciasUsuarios\Schemas\LicenciaUsuarioInfolist;
use App\Filament\Resources\LicenciasUsuarios\Tables\LicenciasUsuariosTable;
use App\Models\LicenciaUsuario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LicenciaUsuarioResource extends Resource
{
    protected static ?string $model = LicenciaUsuario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Software y Licencias';

    protected static ?string $navigationLabel = 'Licencias de usuarios';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'licencia de usuario';

    protected static ?string $pluralModelLabel = 'licencias de usuarios';

    public static function form(Schema $schema): Schema
    {
        return LicenciaUsuarioForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LicenciaUsuarioInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenciasUsuariosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenciasUsuarios::route('/'),
            'create' => CreateLicenciaUsuario::route('/create'),
            'view' => ViewLicenciaUsuario::route('/{record}'),
            'edit' => EditLicenciaUsuario::route('/{record}/edit'),
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
