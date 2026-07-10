<?php

namespace App\Filament\Resources\AccUdCompartidas\Schemas;

use App\Filament\Resources\Users\RelationManagers\UnidadesCompartidasRelationManager;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccUdCompartidaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la unidad compartida')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->hiddenOn(UnidadesCompartidasRelationManager::class),
                                TextInput::make('unidad_compartida')
                                    ->label('Unidad compartida')
                                    ->required()
                                    ->maxLength(100),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
