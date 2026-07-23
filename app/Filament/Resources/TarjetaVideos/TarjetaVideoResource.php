<?php

namespace App\Filament\Resources\TarjetaVideos;

use App\Filament\Resources\TarjetaVideos\Pages\ManageTarjetaVideos;
use App\Models\TarjetaVideo;
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
use UnitEnum;

class TarjetaVideoResource extends Resource
{
    protected static ?string $model = TarjetaVideo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Inventario Equipos y Componentes';

    protected static ?string $navigationLabel = 'Tarjetas de video';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'tarjeta de video';

    protected static ?string $pluralModelLabel = 'tarjetas de video';

    protected static ?string $recordTitleAttribute = 'nombre_gpu';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la tarjeta de video')
                    ->description('Catálogo de GPUs disponibles para los equipos.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nombre_gpu')
                            ->label('Nombre de la GPU')
                            ->placeholder('Ej. NVIDIA RTX 4050')
                            ->helperText('Debe ser único. Máximo 100 caracteres.')
                            ->required()
                            ->minLength(2)
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'El nombre de la GPU es obligatorio.',
                                'min' => 'El nombre debe tener al menos 2 caracteres.',
                                'max' => 'El nombre no puede superar 100 caracteres.',
                                'unique' => 'Ya existe una tarjeta de video con este nombre.',
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
                TextEntry::make('nombre_gpu')
                    ->label('Nombre de la GPU'),
                TextEntry::make('equipos_count')
                    ->label('Equipos asociados')
                    ->state(fn (TarjetaVideo $record): int => $record->equipos()->count())
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
            ->defaultSort('nombre_gpu')
            ->columns([
                TextColumn::make('nombre_gpu')
                    ->label('GPU')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedRectangleGroup),
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
                        ->successNotificationTitle('Tarjeta de video actualizada correctamente'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar tarjeta de video')
                        ->modalDescription('Si la GPU está asociada a equipos, la eliminación puede fallar por integridad referencial.')
                        ->successNotificationTitle('Tarjeta de video eliminada correctamente'),
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
                        ->label('Eliminar seleccionadas')
                        ->requiresConfirmation()
                        ->successNotificationTitle('Tarjetas de video eliminadas correctamente'),
                ])->label('Acciones masivas'),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre_gpu'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTarjetaVideos::route('/'),
        ];
    }
}
