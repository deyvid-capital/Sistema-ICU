<?php

namespace App\Filament\Resources\AccesoInternets\Schemas;

use App\Filament\Resources\Concerns\ShowsUsuarioFortitoken;
use App\Filament\Resources\Users\RelationManagers\AccesosInternetRelationManager;
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

class AccesoInternetForm
{
    use ShowsUsuarioFortitoken;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del acceso a Internet')
                    ->description('Agrega a quién se le va a otorgar acceso a Internet, y qué nivel de acceso tiene.')
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
                                    ->live()
                                    ->rule(new NotTrashed(User::class))
                                    ->hiddenOn(AccesosInternetRelationManager::class),
                                static::fortitokenFormPlaceholder(),
                                TextInput::make('usuario_red')
                                    ->label('Usuario de red')
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
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required(),
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
