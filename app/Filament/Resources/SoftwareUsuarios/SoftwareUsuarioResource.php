<?php

namespace App\Filament\Resources\SoftwareUsuarios;

use App\Filament\Resources\SoftwareUsuarios\Pages\CreateSoftwareUsuario;
use App\Filament\Resources\SoftwareUsuarios\Pages\EditSoftwareUsuario;
use App\Filament\Resources\SoftwareUsuarios\Pages\ListSoftwareUsuarios;
use App\Filament\Resources\SoftwareUsuarios\Pages\ViewSoftwareUsuario;
use App\Filament\Resources\SoftwareUsuarios\Schemas\SoftwareUsuarioForm;
use App\Filament\Resources\SoftwareUsuarios\Schemas\SoftwareUsuarioInfolist;
use App\Filament\Resources\SoftwareUsuarios\Tables\SoftwareUsuariosTable;
use App\Models\SoftwareUsuario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SoftwareUsuarioResource extends Resource
{
    protected static ?string $model = SoftwareUsuario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Software y Licencias';

    protected static ?string $navigationLabel = 'Software de usuarios';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'software de usuario';

    protected static ?string $pluralModelLabel = 'software de usuarios';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SoftwareUsuarioForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SoftwareUsuarioInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoftwareUsuariosTable::configure($table);
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
        return ['estado', 'usuario.name', 'software.nombre_software'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSoftwareUsuarios::route('/'),
            'create' => CreateSoftwareUsuario::route('/create'),
            'view' => ViewSoftwareUsuario::route('/{record}'),
            'edit' => EditSoftwareUsuario::route('/{record}/edit'),
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
