<?php

namespace App\Filament\Resources\Virtuals;

use App\Filament\Resources\Virtuals\Pages\ManageVirtuals;
use App\Models\Virtual;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class VirtualResource extends Resource
{
    protected static ?string $model = Virtual::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static string|UnitEnum|null $navigationGroup = 'Fondos y Recursos Compartidos';

    protected static ?string $navigationLabel = 'Máquinas virtuales';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'máquina virtual';

    protected static ?string $pluralModelLabel = 'máquinas virtuales';

    protected static ?string $recordTitleAttribute = 'maquina_virtual';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la máquina virtual')
                    ->description('Aquí puedes agregar información de la máquina virtual')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('maquina_virtual')
                            ->label('Nombre de la VM')
                            ->required()
                            ->maxLength(100),
                        Toggle::make('estado')
                            ->label('Activa')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('maquina_virtual')
                    ->label('Nombre de la VM'),
                IconEntry::make('estado')
                    ->label('Activa')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('maquina_virtual')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('maquina_virtual')
                    ->label('Máquina virtual')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedCloud),
                IconColumn::make('estado')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activa',
                        '0' => 'Inactiva',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->successNotificationTitle('Máquina virtual eliminada correctamente'),
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
                        ->successNotificationTitle('Máquinas virtuales eliminadas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVirtuals::route('/'),
        ];
    }
}
