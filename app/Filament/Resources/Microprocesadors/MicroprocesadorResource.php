<?php

namespace App\Filament\Resources\Microprocesadors;

use App\Filament\Clusters\Inventario\InventarioCluster;
use App\Filament\Resources\Microprocesadors\Pages\ManageMicroprocesadors;
use App\Models\Microprocesador;
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

class MicroprocesadorResource extends Resource
{
    protected static ?string $model = Microprocesador::class;

    protected static ?string $cluster = InventarioCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Microprocesadores';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'microprocesador';

    protected static ?string $pluralModelLabel = 'microprocesadores';

    protected static ?string $recordTitleAttribute = 'nombre_micro';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del microprocesador')
                    ->description('Catálogo de procesadores disponibles para los equipos.')
                    ->icon(Heroicon::OutlinedCpuChip)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nombre_micro')
                            ->label('Nombre del procesador')
                            ->placeholder('Ej. Intel Core i7-1365U')
                            ->helperText('Debe ser único. Máximo 100 caracteres.')
                            ->required()
                            ->minLength(2)
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'El nombre del procesador es obligatorio.',
                                'min' => 'El nombre debe tener al menos 2 caracteres.',
                                'max' => 'El nombre no puede superar 100 caracteres.',
                                'unique' => 'Ya existe un microprocesador con este nombre.',
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
                TextEntry::make('nombre_micro')
                    ->label('Nombre del procesador'),
                TextEntry::make('equipos_count')
                    ->label('Equipos asociados')
                    ->state(fn (Microprocesador $record): int => $record->equipos()->count())
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
            ->defaultSort('nombre_micro')
            ->columns([
                TextColumn::make('nombre_micro')
                    ->label('Procesador')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedCpuChip),
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
                        ->successNotificationTitle('Microprocesador actualizado correctamente'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar microprocesador')
                        ->modalDescription('Si el procesador está asociado a equipos, la eliminación puede fallar por integridad referencial.')
                        ->successNotificationTitle('Microprocesador eliminado correctamente'),
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
                        ->successNotificationTitle('Microprocesadores eliminados correctamente'),
                ])->label('Acciones masivas'),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre_micro'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMicroprocesadors::route('/'),
        ];
    }
}
