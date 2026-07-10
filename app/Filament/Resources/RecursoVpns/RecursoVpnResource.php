<?php

namespace App\Filament\Resources\RecursoVpns;

use App\Filament\Resources\RecursoVpns\Pages\ManageRecursoVpns;
use App\Models\RecursoVpn;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
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

class RecursoVpnResource extends Resource
{
    protected static ?string $model = RecursoVpn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|UnitEnum|null $navigationGroup = 'Fondos y Recursos Compartidos';

    protected static ?string $navigationLabel = 'Recursos VPN';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'recurso VPN';

    protected static ?string $pluralModelLabel = 'recursos VPN';

    protected static ?string $recordTitleAttribute = 'nombre_vpn';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del recurso VPN')
                    ->description('Datos del recurso VPN')
                    ->schema([
                        TextInput::make('nombre_vpn')
                            ->label('Nombre del recurso')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),
                        Toggle::make('estado')
                            ->label('Activo')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);

    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre_vpn')
                    ->label('Nombre del recurso'),
                IconEntry::make('estado')
                    ->label('Activo')
                    ->boolean(),
                TextEntry::make('descripcion')
                    ->label('Descripción')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nombre_vpn')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('nombre_vpn')
                    ->label('Recurso VPN')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedLockClosed),
                IconColumn::make('estado')
                    ->label('Activo')
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
                        '1' => 'Activo',
                        '0' => 'Inactivo',
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
                        ->successNotificationTitle('Recurso VPN eliminado correctamente'),
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
                        ->successNotificationTitle('Recursos VPN eliminados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRecursoVpns::route('/'),
        ];
    }
}
