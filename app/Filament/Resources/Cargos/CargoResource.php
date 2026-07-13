<?php

namespace App\Filament\Resources\Cargos;

use App\Filament\Resources\Areas\RelationManagers\CargosRelationManager;
use App\Filament\Resources\Cargos\Pages\ManageCargos;
use App\Models\Cargo;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CargoResource extends Resource
{
    protected static ?string $model = Cargo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $recordTitleAttribute = 'cargo';

    protected static ?string $navigationLabel = 'Cargos';

    protected static ?string $modelLabel = 'cargo';

    protected static ?string $pluralModelLabel = 'cargos';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion de usuarios';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('area_id')
                    ->label('Área')
                    ->relationship('area', 'area')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->hiddenOn(CargosRelationManager::class),
                TextInput::make('cargo')
                    ->label('Cargo')
                    ->required()
                    ->maxLength(150)
                    ->unique(Cargo::class, 'cargo', ignoreRecord: true),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('area.area')
                    ->label('Área')
                    ->placeholder('Sin área asignada'),
                TextEntry::make('cargo')
                    ->label('Cargo'),
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
            ->recordTitleAttribute('cargo')
            ->defaultSort('cargo')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('area.area')
                    ->label('Área')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('Sin área asignada')
                    ->hiddenOn(CargosRelationManager::class),
                TextColumn::make('cargo')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedBriefcase),
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
                SelectFilter::make('area_id')
                    ->label('Área')
                    ->relationship('area', 'area')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->successNotificationTitle('Cargo actualizado correctamente'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->successNotificationTitle('Cargo eliminado correctamente'),
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
                        ->successNotificationTitle('Cargos eliminados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCargos::route('/'),
        ];
    }
}
