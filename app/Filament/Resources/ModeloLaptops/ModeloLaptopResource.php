<?php

namespace App\Filament\Resources\ModeloLaptops;

use App\Filament\Clusters\Inventario\InventarioCluster;
use App\Filament\Resources\ModeloLaptops\Pages\ManageModeloLaptops;
use App\Models\ModeloLaptop;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModeloLaptopResource extends Resource
{
    protected static ?string $model = ModeloLaptop::class;

    protected static ?string $cluster = InventarioCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Modelos de laptop';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'modelo de laptop';

    protected static ?string $pluralModelLabel = 'modelos de laptop';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del modelo')
                    ->description('Catálogo de modelos de laptop disponibles para el inventario.')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre del modelo')
                            ->placeholder('Ej. Latitude 5540')
                            ->helperText('Debe ser único. Máximo 100 caracteres.')
                            ->required()
                            ->minLength(2)
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'El nombre del modelo es obligatorio.',
                                'min' => 'El nombre debe tener al menos 2 caracteres.',
                                'max' => 'El nombre no puede superar 100 caracteres.',
                                'unique' => 'Ya existe un modelo con este nombre.',
                            ])
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                            ->autocomplete(false),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre')
                    ->label('Nombre del modelo'),
                TextEntry::make('equipos_count')
                    ->label('Equipos asociados')
                    ->state(fn (ModeloLaptop $record): int => $record->equipos()->count())
                    ->badge()
                    ->color('info'),
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
            ->modifyQueryUsing(fn ($query) => $query->withCount('equipos'))
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedComputerDesktop),
                TextColumn::make('equipos_count')
                    ->label('Equipos')
                    ->counts('equipos')
                    ->badge()
                    ->color('info')
                    ->sortable(),
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
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->successNotificationTitle('Modelo actualizado correctamente'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar modelo de laptop')
                        ->modalDescription('Si el modelo está asociado a equipos, la eliminación puede fallar por integridad referencial.')
                        ->successNotificationTitle('Modelo eliminado correctamente'),
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
                        ->requiresConfirmation()
                        ->successNotificationTitle('Modelos eliminados correctamente'),
                ])->label('Acciones masivas'),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageModeloLaptops::route('/'),
        ];
    }
}
