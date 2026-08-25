<?php

namespace App\Filament\Resources\NotaSalidaEquipos;

use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Resources\NotaSalidaEquipos\Pages\CreateNotaSalidaEquipo;
use App\Filament\Resources\NotaSalidaEquipos\Pages\EditNotaSalidaEquipo;
use App\Filament\Resources\NotaSalidaEquipos\Pages\ListNotaSalidaEquipos;
use App\Filament\Resources\NotaSalidaEquipos\Pages\ViewNotaSalidaEquipo;
use App\Filament\Resources\NotaSalidaEquipos\RelationManagers\FirmantesRelationManager;
use App\Filament\Resources\NotaSalidaEquipos\RelationManagers\PdfsRelationManager;
use App\Filament\Resources\NotaSalidaEquipos\Schemas\NotaSalidaEquipoForm;
use App\Filament\Resources\NotaSalidaEquipos\Schemas\NotaSalidaEquipoInfolist;
use App\Filament\Resources\NotaSalidaEquipos\Tables\NotaSalidaEquiposTable;
use App\Models\NotaSalidaEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotaSalidaEquipoResource extends Resource
{
    protected static ?string $model = NotaSalidaEquipo::class;

    protected static ?string $cluster = EquiposCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightStartOnRectangle;

    protected static ?string $navigationLabel = 'Notas de salida';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'nota de salida';

    protected static ?string $pluralModelLabel = 'notas de salida';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return NotaSalidaEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NotaSalidaEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotaSalidaEquiposTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FirmantesRelationManager::class,
            PdfsRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['numero', 'nota', 'usuarioExterno.nombre'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotaSalidaEquipos::route('/'),
            'create' => CreateNotaSalidaEquipo::route('/create'),
            'view' => ViewNotaSalidaEquipo::route('/{record}'),
            'edit' => EditNotaSalidaEquipo::route('/{record}/edit'),
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
