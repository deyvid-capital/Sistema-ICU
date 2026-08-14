<?php

namespace App\Filament\Resources\Areas;

use App\Filament\Clusters\Usuarios\UsuariosCluster;
use App\Filament\Imports\AreaImporter;
use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Filament\Resources\Areas\Pages\EditArea;
use App\Filament\Resources\Areas\Pages\ListAreas;
use App\Filament\Resources\Areas\Pages\ViewArea;
use App\Filament\Resources\Areas\RelationManagers\CargosRelationManager;
use App\Models\Area;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ImportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static ?string $cluster = UsuariosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'area';

    protected static ?string $navigationLabel = 'Áreas';

    protected static ?string $modelLabel = 'área';

    protected static ?string $pluralModelLabel = 'áreas';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('area')
                    ->label('Área')
                    ->required(),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('area')
                    ->label('Área'),
                TextEntry::make('descripcion')
                    ->label('Descripción')
                    ->placeholder('Sin descripción')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('area')
            ->defaultSort('area')
            ->striped()
            ->paginated([10, 25, 50])
            ->headerActions([
                ImportAction::make()
                    ->importer(AreaImporter::class)
                    ->maxRows(200)
                    ->label('Importar'),
            ])
            ->columns([
                TextColumn::make('area')
                    ->label('Área')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedBuildingOffice2),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->placeholder('Sin descripción')
                    ->toggleable(),
                TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                TextColumn::make('cargos_count')
                    ->label('Cargos')
                    ->counts('cargos')
                    ->badge()
                    ->color('success'),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->successNotificationTitle('Área actualizada correctamente'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->successNotificationTitle('Área eliminada correctamente'),
                ])
                    ->label('Acciones')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('info')
                    ->button()
                    ->tooltip('Acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->successNotificationTitle('Áreas eliminadas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CargosRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['area', 'descripcion'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAreas::route('/'),
            'create' => CreateArea::route('/create'),
            'view' => ViewArea::route('/{record}'),
            'edit' => EditArea::route('/{record}/edit'),
        ];
    }
}
