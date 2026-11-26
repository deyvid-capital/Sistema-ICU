<?php

namespace App\Filament\Resources\UsuariosExternos;

use App\Filament\Clusters\Usuarios\UsuariosCluster;
use App\Filament\Resources\UsuariosExternos\Pages\CreateUsuarioExterno;
use App\Filament\Resources\UsuariosExternos\Pages\EditUsuarioExterno;
use App\Filament\Resources\UsuariosExternos\Pages\ListUsuariosExternos;
use App\Filament\Resources\UsuariosExternos\Pages\ViewUsuarioExterno;
use App\Filament\Resources\UsuariosExternos\Schemas\UsuarioExternoForm;
use App\Filament\Resources\UsuariosExternos\Schemas\UsuarioExternoInfolist;
use App\Filament\Resources\UsuariosExternos\Tables\UsuariosExternosTable;
use App\Models\UsuarioExterno;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsuarioExternoResource extends Resource
{
    protected static ?string $model = UsuarioExterno::class;

    protected static ?string $cluster = UsuariosCluster::class;

    protected static ?string $slug = 'usuarios-externos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static ?string $navigationLabel = 'Usuarios externos';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'usuario externo';

    protected static ?string $pluralModelLabel = 'usuarios externos';

    protected static ?string $recordTitleAttribute = 'nombre_contacto';

    public static function form(Schema $schema): Schema
    {
        return UsuarioExternoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UsuarioExternoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsuariosExternosTable::configure($table);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['numero', 'nombre_contacto', 'empresa', 'cargo', 'correo', 'motivo'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuariosExternos::route('/'),
            'create' => CreateUsuarioExterno::route('/create'),
            'view' => ViewUsuarioExterno::route('/{record}'),
            'edit' => EditUsuarioExterno::route('/{record}/edit'),
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
