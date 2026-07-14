<?php

namespace App\Filament\Resources\MemoriaRams;

use App\Filament\Resources\MemoriaRams\Pages\ManageMemoriaRams;
use App\Models\MemoriaRam;
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

class MemoriaRamResource extends Resource
{
    protected static ?string $model = MemoriaRam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Inventario Equipos y Componentes';

    protected static ?string $navigationLabel = 'Memorias RAM';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'memoria RAM';

    protected static ?string $pluralModelLabel = 'memorias RAM';

    protected static ?string $recordTitleAttribute = 'tipo_ram';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la memoria RAM')
                    ->description('Catálogo de tipos de memoria RAM usados en los equipos.')
                    ->icon(Heroicon::OutlinedCircleStack)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('tipo_ram')
                            ->label('Tipo de RAM')
                            ->placeholder('Ej. 16GB DDR4')
                            ->helperText('Debe ser único. Máximo 50 caracteres.')
                            ->required()
                            ->minLength(2)
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'El tipo de RAM es obligatorio.',
                                'min' => 'El tipo debe tener al menos 2 caracteres.',
                                'max' => 'El tipo no puede superar 50 caracteres.',
                                'unique' => 'Ya existe este tipo de memoria RAM.',
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
                TextEntry::make('tipo_ram')
                    ->label('Tipo de RAM'),
                TextEntry::make('equipos_count')
                    ->label('Equipos asociados')
                    ->state(fn (MemoriaRam $record): int => $record->equipos()->count())
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
            ->defaultSort('tipo_ram')
            ->columns([
                TextColumn::make('tipo_ram')
                    ->label('Tipo de RAM')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedCircleStack),
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
                        ->successNotificationTitle('Memoria RAM actualizada correctamente'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar memoria RAM')
                        ->modalDescription('Si el tipo de RAM está asociado a equipos, la eliminación puede fallar por integridad referencial.')
                        ->successNotificationTitle('Memoria RAM eliminada correctamente'),
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
                        ->successNotificationTitle('Memorias RAM eliminadas correctamente'),
                ])->label('Acciones masivas'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMemoriaRams::route('/'),
        ];
    }
}
