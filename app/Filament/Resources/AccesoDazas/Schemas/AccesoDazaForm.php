<?php

namespace App\Filament\Resources\AccesoDazas\Schemas;

use App\Filament\Resources\Users\RelationManagers\AccesosDazaRelationManager;
use App\Models\Fondo;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccesoDazaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del acceso DAZA')
                    ->description('Aquí puedes agregar información del acceso DAZA')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(User::class))
                                    ->hiddenOn(AccesosDazaRelationManager::class),
                                TextInput::make('usuario_daza')
                                    ->label('Usuario DAZA')
                                    ->maxLength(50),
                                Select::make('nivel_acceso')
                                    ->label('Nivel de acceso')
                                    ->native(false)
                                    ->options([
                                        'Usuario Estándar' => 'Usuario Estándar',
                                        'Usuario Administrador' => 'Usuario Administrador',
                                        'Usuario de Soporte' => 'Usuario de Soporte',
                                        'Usuario de Administración' => 'Usuario de Administración',
                                    ])
                                    ->required(),
                                Select::make('fondo_id')
                                    ->label('Fondo')
                                    ->relationship('fondo', 'nombre_completo')
                                    ->getOptionLabelFromRecordUsing(fn (Fondo $record): string => $record->nombre_completo ?? $record->codigo)
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('ambiente')
                                    ->label('Ambiente')
                                    ->native(false)
                                    ->options([
                                        'Producción' => 'Producción',
                                        'Contingencia' => 'Contingencia',
                                    ])
                                    ->required(),
                                DatePicker::make('fecha')
                                    ->default(now())
                                    ->label('Fecha'),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
