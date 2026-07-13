<?php

namespace App\Filament\Resources\AccesoSistemas\Schemas;

use App\Filament\Resources\Users\RelationManagers\AccesosSistemasRelationManager;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccesoSistemaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del acceso al sistema')
                    ->description('Aquí puedes agregar información del acceso al sistema')
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
                                    ->hiddenOn(AccesosSistemasRelationManager::class),
                                Select::make('id_sistema')
                                    ->label('Sistema')
                                    ->relationship('sistema', 'nombre_sistema')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('usuario_sistema')
                                    ->label('Usuario del sistema')
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
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha de inicio')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de fin')
                                    ->afterOrEqual('fecha_inicio'),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
